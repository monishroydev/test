<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SpeedTestController extends Controller
{

    public function index()
    {
        return view('internet-speedtest');
    }
    /**
     * Handles the Ping (Latency) test.
     * The client measures the RTT. This just provides a simple, fast response.
     */
    public function ping()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'pong',
        ]);
    }

    /**
     * Handles the Download test.
     * Generates and serves a large (1.5MB) binary data stream.
     */
    public function download()
    {
        // 1. Define the size of the data blob in bytes (1.5 MB)
        $size_bytes = 1024 * 1024 * 1.5;

        // 2. Generate random bytes to ensure no compression occurs, leading to an accurate test.
        $data = random_bytes($size_bytes);

        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $size_bytes,
        ];

        return response($data, 200)
            ->withHeaders($headers);
    }

    /**
     * Handles the Upload test.
     * Receives the client's large POST payload and responds once successfully received.
     */
    public function upload(Request $request)
    {
        // Laravel handles decoding the JSON payload automatically
        $uploaded_data = $request->json('data');

        if (empty($uploaded_data)) {
            return response()->json(['status' => 'error', 'message' => 'No data received.'], 400);
        }

        // Successfully received the payload. Client will time this RTT.
        return response()->json([
            'status' => 'success',
            'received_size' => strlen($uploaded_data),
        ], 200);
    }
}
