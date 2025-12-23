<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class YoutubeController extends Controller
{
    public function index()
    {
        return view('youtube');
    }

    public function download(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->url;
        $storagePath = storage_path('app/public/');
        $outputFile = $storagePath . '%(title)s.%(ext)s';

        $cmd = ["yt-dlp", "-f", "best", "-o", $outputFile, $url];

        $process = new Process($cmd);
        $process->setTimeout(null); // disable timeout

        try {
            $process->run(function ($type, $buffer) {
                // this callback captures yt-dlp output
                // you can parse $buffer for progress info if needed
            });

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // find downloaded file
            $files = glob(storage_path('app/public/*.mp4'));
            $latestFile = end($files);

            return response()->download($latestFile);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
