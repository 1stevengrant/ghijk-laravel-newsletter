<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;

class ImageUploadController extends Controller
{
    public function store(Request $request, $campaign = null)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
        ]);

        $uploadedFile = $request->file('image');
        $filename = Str::uuid() . '.jpg'; // Always save as JPEG for email compatibility

        // Create ImageManager instance
        $manager = new ImageManager(new Driver);

        // Process the image using Intervention Image v3
        $image = $manager->read($uploadedFile->getRealPath())
            ->scaleDown(800) // Max width 800px, maintains aspect ratio
            ->toJpeg(85); // Convert to JPEG with 85% quality

        // Store the processed image in campaign-specific directory if campaign ID is provided
        if ($campaign) {
            $path = "campaign-images/campaign-{$campaign}/{$filename}";
            // Ensure the campaign directory exists
            Storage::disk('public')->makeDirectory("campaign-images/campaign-{$campaign}");
        } else {
            $path = "campaign-images/{$filename}";
        }

        Storage::disk('public')->put($path, $image->toString());

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'full_url' => url(Storage::disk('public')->url($path)),
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // Security check: only allow deleting files in campaign-images directory
        if (! str_starts_with($path, 'campaign-images/')) {
            return response()->json(['error' => 'Invalid path'], 400);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['success' => true]);
    }
}
