<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class UrlExpanderController extends Controller
{
    public function index()
    {
        return view('url-expander');
    }
    
    public function expandUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid URL provided'
            ], 400);
        }

        $url = $request->input('url');
        $startTime = microtime(true);

        try {
            $redirectChain = $this->traceRedirects($url);
            $endTime = microtime(true);
            $loadTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds

            return response()->json([
                'success' => true,
                'data' => [
                    'redirects' => $redirectChain,
                    'redirect_count' => count($redirectChain) - 1,
                    'load_time' => $loadTime,
                    'final_url' => end($redirectChain)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to expand URL: ' . $e->getMessage()
            ], 500);
        }
    }

    private function traceRedirects($url, $maxRedirects = 10)
    {
        $redirectChain = [$url];
        $currentUrl = $url;
        $redirectCount = 0;

        while ($redirectCount < $maxRedirects) {
            try {
                // Make HTTP request without following redirects automatically
                $response = Http::withOptions([
                    'allow_redirects' => false,
                    'connect_timeout' => 10,
                    'timeout' => 15,
                    'verify' => false, // Be cautious with this in production
                    'http_errors' => false
                ])->get($currentUrl);

                $statusCode = $response->status();

                // Check if it's a redirect status code (3xx)
                if ($statusCode >= 300 && $statusCode < 400) {
                    $location = $response->header('Location');

                    if (!$location) {
                        break;
                    }

                    // Handle relative URLs
                    if (!parse_url($location, PHP_URL_SCHEME)) {
                        $parsedUrl = parse_url($currentUrl);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

                        if (strpos($location, '/') === 0) {
                            $location = $baseUrl . $location;
                        } else {
                            $path = dirname($parsedUrl['path'] ?? '/');
                            $location = $baseUrl . $path . '/' . $location;
                        }
                    }

                    $redirectChain[] = $location;
                    $currentUrl = $location;
                    $redirectCount++;
                } else {
                    // No more redirects
                    break;
                }
            } catch (\Exception $e) {
                // If there's an error, break the loop
                break;
            }
        }

        return $redirectChain;
    }
}
