<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhoisController extends Controller
{
    public function index()
    {
        return view('whois');
    }

    public function lookup(Request $request)
    {
        $domain = $request->input('domain');

        // Replace with your APILayer key
        $apiKey = 'JmwY8NjOFVg4WdIpKkaAGTGGwFFGBvGr';

        $url = "https://api.apilayer.com/whois/query?domain={$domain}";

        $response = Http::withHeaders([
            'apikey' => $apiKey
        ])->get($url);

        if ($response->successful()) {
            Log::info($response->json());
            return response()->json($response->json());
        }

        return response()->json([
            'message' => 'WHOIS lookup failed'
        ], $response->status());
    }
}
