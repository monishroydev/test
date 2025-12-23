<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiTesterController extends Controller
{
    public function index()
    {
        return view('api-tester');
    }

    public function send(Request $request)
    {
        $method = strtoupper($request->input('method'));
        $url = $request->input('url');
        $headers = $request->input('headers', []);
        $body = $request->input('body', []);

        try {
            $response = Http::withHeaders($headers)->send($method, $url, [
                'json' => $body
            ]);

            return response()->json([
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
