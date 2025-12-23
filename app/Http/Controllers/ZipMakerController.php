<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ZipMakerController extends Controller
{
    public function uploadAndZip(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|max:10240', // Max 10MB per file
        ]);

        $zip = new ZipArchive();
        $zipFileName = 'uploaded_files_' . time() . '.zip';
        $zipFilePath = storage_path('app/temp/' . $zipFileName);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
            return response()->json(['error' => 'Failed to create ZIP archive'], 500);
        }

        $addedCount = 0;
        foreach ($request->file('files') as $file) {
            $fileName = $file->getClientOriginalName();
            if ($zip->addFile($file->getPathname(), $fileName)) {
                $addedCount++;
            }
        }
        $zip->close();

        if ($addedCount === 0) {
            unlink($zipFilePath); // Clean up empty ZIP
            return response()->json(['error' => 'No files were added to ZIP'], 400);
        }

        // Stream download
        return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
    }
}
