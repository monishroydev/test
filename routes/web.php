<?php

use App\Http\Controllers\BgRemoverController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageConvertController;
use App\Http\Controllers\WhoisController;

use App\Http\Controllers\ShareController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpeedTestController;
use App\Http\Controllers\YoutubeController;

use App\Http\Controllers\ApiTesterController;
use App\Http\Controllers\ZipMakerController;
use App\Http\Controllers\GrammarController;
use App\Http\Controllers\MetaController;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/api-tester', [ApiTesterController::class, 'index']);
Route::post('/api-tester/send', [ApiTesterController::class, 'send']);

Route::get('/image-convert', [ImageConvertController::class, 'index']);
Route::post('/image-convert', [ImageConvertController::class, 'convert'])->name('image.convert');



Route::get('/whois', [WhoisController::class, 'index']);
Route::post('/whois-lookup', [WhoisController::class, 'lookup'])->name('whois.lookup');


Route::get('/bg-remover', [BgRemoverController::class, 'index']);
Route::post('/remove-bg', [BgRemoverController::class, 'remove'])->name('remove.background');

//Zip Maker
Route::get('/zip-maker', function () {
    return view('zip-maker');
});
Route::post('/upload-and-zip', [ZipMakerController::class, 'uploadAndZip'])->name('zip.upload');

// File Share
Route::get('/file-share', function () {
    return view('file-share');
});

Route::post('/upload', [ShareController::class, 'upload'])->name('upload');
Route::get('/view/{code}', [ShareController::class, 'show'])->name('view');
Route::get('/check/{code}', [ShareController::class, 'check'])->name('check');


// File Share
Route::get('/sitemap-generator', function () {
    return view('sitemap-generator');
});

Route::post('/generate-sitemap', [SitemapController::class, 'generate'])->name('sitemap.generate');
Route::get('/sitemap-progress', [SitemapController::class, 'progress'])->name('sitemap.progress');
Route::get('/sitemap-clear', [SitemapController::class, 'clear'])->name('sitemap.clear');


Route::get('/internet-speedtest', [SpeedTestController::class, 'index'])->name('internet-speedtest.index');


Route::get('/youtube', [YoutubeController::class, 'index']);
Route::post('/download', [YoutubeController::class, 'download'])->name('download.video');

Route::get('/grammar', [GrammarController::class, 'index'])->name('grammar.index');
Route::post('/api/grammar/correct', [GrammarController::class, 'correct'])->name('grammar.correct');

Route::get('/meta-generate', [MetaController::class, 'index'])->name('meta-generate.index');
Route::post('/api/meta-generate', [MetaController::class, 'generate']);
