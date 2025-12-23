<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Share extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_name',
        'files',
        'share_code',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'files' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    // Helper to get total size
    public function getTotalSizeAttribute()
    {
        $total = 0;
        foreach ($this->files ?? [] as $file) {
            $fullPath = storage_path('app/public/' . $file['path']);
            if (file_exists($fullPath)) {
                $total += filesize($fullPath);
            }
        }
        return self::formatBytes($total);
    }

    // Static helper to format bytes (now defined here)
    public static function formatBytes($size, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB');
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }
}
