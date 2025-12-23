<?php

use App\Http\Controllers\SpeedTestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/speedtest/ping', [SpeedTestController::class, 'ping']);

// Download Test
Route::get('/speedtest/download', [SpeedTestController::class, 'download']);

// Upload Test
Route::post('/speedtest/upload', [SpeedTestController::class, 'upload']);