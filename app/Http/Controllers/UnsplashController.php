<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UnsplashService;

class UnsplashController extends Controller
{
    public function __construct(private readonly UnsplashService $unsplashService) {}

    /**
     * Search for photos on Unsplash
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255',
            'page' => 'integer|min:1|max:50',
            'per_page' => 'integer|min:1|max:30',
        ]);

        $results = $this->unsplashService->searchPhotos(
            $request->input('query'),
            $request->input('page', 1),
            $request->input('per_page', 20)
        );

        return response()->json($results);
    }

    /**
     * Download and save an Unsplash photo
     */
    public function download(Request $request, $campaign = null)
    {
        $request->validate([
            'photo_id' => 'required|string',
        ]);

        $result = $this->unsplashService->downloadAndSavePhoto(
            $request->input('photo_id'),
            $campaign
        );

        if (! $result) {
            return response()->json([
                'error' => 'Failed to download and save image',
            ], 500);
        }

        return response()->json($result);
    }
}
