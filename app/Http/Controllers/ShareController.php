<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Share;
use Carbon\Carbon;

class ShareController extends Controller
{
    // Upload multiple files
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|array|max:5', // Limit to 5 files for practicality
            'file.*' => 'file|max:200000', // Each <200MB
        ]);

        $shareCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addHours(24);

        $files = [];
        $totalSize = 0;

        foreach ($request->file('file') as $file) {
            $originalName = $file->getClientOriginalName();
            $path = $file->store('uploads', 'public');
            $files[] = ['name' => $originalName, 'path' => $path];
            $totalSize += $file->getSize();
        }

        Share::create([
            'original_name' => count($files) . ' files shared',
            'files' => $files,
            'share_code' => $shareCode,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'share_code' => $shareCode,
            'message' => 'Upload successful! Share with: /view/' . $shareCode,
            'files' => $files, // Array of ['name' => , 'path' => ] for frontend
            'total_size' => Share::formatBytes($totalSize),
        ]);
    }

    // Show file details for view (UPDATED: Use public storage URLs for direct download)
    public function show($code)
    {
        $share = Share::active()->where('share_code', $code)->firstOrFail();

        $shareFiles = $share->files ?? [];
        $shareFilesFormatted = [];
        foreach ($shareFiles as $file) {
            $fullPath = storage_path('app/public/' . $file['path']);
            if (file_exists($fullPath)) {
                $shareFilesFormatted[] = [
                    'name' => $file['name'],
                    'size' => Share::formatBytes(filesize($fullPath)),
                    'download_url' => url('storage/' . $file['path']), // Direct public URL via storage link
                ];
            }
        }

        return response()->json([
            'files' => $shareFilesFormatted,
            'total_size' => $share->total_size,
            'uploaded_at' => $share->created_at->format('Y-m-d H:i:s'),
            'expires_at' => $share->expires_at->format('Y-m-d H:i:s'),
        ]);
    }

    // Check if code exists
    public function check($code)
    {
        $share = Share::active()->where('share_code', $code)->first();
        return response()->json(['exists' => !!$share]);
    }
}
