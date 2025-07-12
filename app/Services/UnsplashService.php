<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Http\Integrations\UnsplashConnector;
use Saloon\Exceptions\Request\RequestException;
use App\Http\Integrations\Requests\GetPhotoRequest;
use App\Http\Integrations\Requests\SearchPhotosRequest;
use App\Http\Integrations\Requests\TrackDownloadRequest;

class UnsplashService
{
    private UnsplashConnector $connector;

    public function __construct()
    {
        $this->connector = new UnsplashConnector;
    }

    /**
     * Search for photos on Unsplash
     */
    public function searchPhotos(string $query, int $page = 1, int $perPage = 20): array
    {
        try {
            $request = new SearchPhotosRequest($query, $page, $perPage);
            $response = $this->connector->send($request);

            if ($response->failed()) {
                Log::error('Unsplash API error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json();

            return [
                'results' => $this->formatPhotos($data['results'] ?? []),
                'total' => $data['total'] ?? 0,
                'total_pages' => $data['total_pages'] ?? 0,
            ];
        } catch (RequestException $e) {
            Log::error('Unsplash search error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get a specific photo by ID
     */
    public function getPhoto(string $photoId): ?array
    {
        try {
            $request = new GetPhotoRequest($photoId);
            $response = $this->connector->send($request);

            if ($response->failed()) {
                Log::error('Unsplash photo fetch error', [
                    'photo_id' => $photoId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            return $this->formatPhoto($response->json());
        } catch (RequestException $e) {
            Log::error('Unsplash photo fetch error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Track download for Unsplash API requirements
     */
    public function trackDownload(string $photoId): bool
    {
        try {
            $request = new TrackDownloadRequest($photoId);
            $response = $this->connector->send($request);

            return $response->successful();
        } catch (RequestException $e) {
            Log::error('Unsplash download tracking error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Download and save an Unsplash photo to local storage
     */
    public function downloadAndSavePhoto(string $photoId, ?int $campaignId = null): ?array
    {
        try {
            // First get the photo details
            $photo = $this->getPhoto($photoId);
            if (! $photo) {
                return null;
            }

            // Track the download (required by Unsplash API)
            $this->trackDownload($photoId);

            // Download the image from Unsplash (use regular size for good quality/performance balance)
            $imageUrl = $photo['urls']['regular'];
            $imageResponse = \Illuminate\Support\Facades\Http::get($imageUrl);

            if ($imageResponse->failed()) {
                Log::error('Failed to download Unsplash image', [
                    'photo_id' => $photoId,
                    'url' => $imageUrl,
                ]);

                return null;
            }

            // Process and save the image using existing image processing logic
            return $this->processAndSaveImage(
                $imageResponse->body(),
                $photo,
                $campaignId
            );

        } catch (\Exception $e) {
            Log::error('Unsplash download and save error', [
                'photo_id' => $photoId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Process and save downloaded image using existing image processing pipeline
     */
    private function processAndSaveImage(string $imageData, array $photo, ?int $campaignId = null): array
    {
        $filename = \Illuminate\Support\Str::uuid() . '.jpg';

        // Create ImageManager instance (same as ImageUploadController)
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver);

        // Process the image
        $processedImage = $manager->read($imageData)
            ->scaleDown(800); // Max width 800px, maintains aspect ratio

        // Get dimensions
        $width = $processedImage->width();
        $height = $processedImage->height();

        // Encode to JPEG
        $encodedImage = $processedImage->toJpeg(85);

        // Store the processed image
        if ($campaignId) {
            $path = "campaign-images/campaign-{$campaignId}/{$filename}";
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory("campaign-images/campaign-{$campaignId}");
        } else {
            $path = "campaign-images/{$filename}";
        }

        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $encodedImage->toString());

        // Create Image record in database
        $image = \App\Models\Image::create([
            'filename' => $filename,
            'path' => $path,
            'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
            'original_filename' => 'unsplash-' . $photo['id'] . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => mb_strlen($encodedImage->toString()),
            'width' => $width,
            'height' => $height,
            'alt_text' => $photo['description'],
            'user_id' => auth()->id(),
        ]);

        return [
            'success' => true,
            'id' => $image->id,
            'path' => $path,
            'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
            'full_url' => url(\Illuminate\Support\Facades\Storage::disk('public')->url($path)),
            'unsplash_attribution' => [
                'photographer' => $photo['user']['name'],
                'photographer_url' => $photo['user']['profile_url'],
                'unsplash_url' => 'https://unsplash.com/photos/' . $photo['id'],
            ],
        ];
    }

    /**
     * Format multiple photos for consistent output
     */
    private function formatPhotos(array $photos): array
    {
        return array_map([$this, 'formatPhoto'], $photos);
    }

    /**
     * Format a single photo for consistent output
     */
    private function formatPhoto(array $photo): array
    {
        return [
            'id' => $photo['id'],
            'description' => $photo['description'] ?? $photo['alt_description'] ?? '',
            'urls' => [
                'thumb' => $photo['urls']['thumb'] ?? '',
                'small' => $photo['urls']['small'] ?? '',
                'regular' => $photo['urls']['regular'] ?? '',
                'full' => $photo['urls']['full'] ?? '',
                'raw' => $photo['urls']['raw'] ?? '',
            ],
            'links' => [
                'download' => $photo['links']['download'] ?? '',
                'download_location' => $photo['links']['download_location'] ?? '',
            ],
            'width' => $photo['width'] ?? 0,
            'height' => $photo['height'] ?? 0,
            'user' => [
                'name' => $photo['user']['name'] ?? '',
                'username' => $photo['user']['username'] ?? '',
                'profile_url' => $photo['user']['links']['html'] ?? '',
            ],
            'attribution_required' => true,
        ];
    }
}
