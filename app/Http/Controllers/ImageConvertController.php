<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageConvertController extends Controller
{
    protected $imageManager;

    public function __construct(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function index()
    {
        return view('image-convert');
    }

    public function convert(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
            'format' => 'required|in:jpg,jpeg,png,webp,bmp,gif',
        ]);

        $format = $request->format;
        $uploadedImage = $request->file('image');

        $manager = new ImageManager(new Driver());

        $image = $manager->read($uploadedImage->getRealPath());

        $convertedName = Str::random(10) . '.' . $format;

        // encod jpeg data
        $encoded = $image->toWebp(60); // Convert to desired format

        // Store to public disk
        Storage::disk('public')->put($convertedName, (string) $encoded->toString());

        return back()->with('success', 'Image converted!')
            ->with('converted_image', asset('storage/' . $convertedName));
    }
}
