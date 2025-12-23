<?php

// app/Http/Controllers/BgRemoverController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Iloveimg\Iloveimg;

class BgRemoverController extends Controller
{
    public function index()
    {
        return view('bg-remover');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ]);

        $file = $request->file('image');
        $path = $file->getRealPath();

    
        try {
            $iloveimg = new Iloveimg(
                'project_public_1a42aad2916fb1b460b5e9ac07c6b080_suaez558d71d981959820e8f9879374d11adc',
                'secret_key_585011d8af54eb3f1899d2017319f494_Aym9V3bfc4370ea1000f268ab5a49ca2cf48a'
            );

            $task = $iloveimg->newTask('removebg'); // ✅ Correct task name
            $task->addFile($path);
            $task->execute();
            $task->download(public_path('uploads'));

            $files = glob(public_path('uploads/*'));
            usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
            $outputFile = basename($files[0]);

            return response()->json([
                'status' => 'success',
                'image' => asset('uploads/' . $outputFile),
            ]);
        } catch (\Exception $e) {
            Log::error('iLoveIMG SDK Error', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'API failed',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }
}
