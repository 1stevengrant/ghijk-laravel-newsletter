<?php

namespace App\Http\Controllers;

use App\Models\Image;
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
        $processedImage = $manager->read($uploadedFile->getRealPath())
            ->scaleDown(800); // Max width 800px, maintains aspect ratio

        // Get dimensions before encoding
        $width = $processedImage->width();
        $height = $processedImage->height();

        // Encode to JPEG
        $encodedImage = $processedImage->toJpeg(85);

        // Store the processed image in campaign-specific directory if campaign ID is provided
        if ($campaign) {
            $path = "campaign-images/campaign-{$campaign}/{$filename}";
            // Ensure the campaign directory exists
            Storage::disk('public')->makeDirectory("campaign-images/campaign-{$campaign}");
        } else {
            $path = "campaign-images/{$filename}";
        }

        Storage::disk('public')->put($path, $encodedImage->toString());

        // Create Image record in database
        $image = Image::create([
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'mime_type' => 'image/jpeg',
            'size' => mb_strlen($encodedImage->toString()),
            'width' => $width,
            'height' => $height,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $image->id,
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

    public function index(Request $request)
    {
        $images = Image::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'images' => $images->items(),
            'has_more' => $images->hasMorePages(),
            'current_page' => $images->currentPage(),
            'total' => $images->total(),
        ]);
    }
}
