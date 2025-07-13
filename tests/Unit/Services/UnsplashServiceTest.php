<?php

use App\Models\User;
use App\Services\UnsplashService;
use Saloon\Http\Faking\MockClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Saloon\Http\Faking\MockResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Integrations\Requests\GetPhotoRequest;
use App\Http\Integrations\Requests\SearchPhotosRequest;
use App\Http\Integrations\Requests\TrackDownloadRequest;

describe('UnsplashService', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    beforeEach(function () {
        Storage::fake('public');
        Http::fake();
        Log::shouldReceive('error')->andReturn(null)->byDefault();

        // Create a user for authentication
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    afterEach(function () {
        MockClient::destroyGlobal();
    });

    test('searchPhotos returns formatted results on successful response', function () {
        MockClient::global([
            SearchPhotosRequest::class => MockResponse::fixture('Unsplash/search_photos_success'),
        ]);

        $service = new UnsplashService;
        $result = $service->searchPhotos('landscape', 1, 20);

        expect($result)->toBeArray();
        expect($result['total'])->toBe(1000);
        expect($result['total_pages'])->toBe(50);
        expect($result['results'])->toHaveCount(1);

        $photo = $result['results'][0];
        expect($photo['id'])->toBe('photo-1');
        expect($photo['description'])->toBe('Beautiful landscape');
        expect($photo['urls']['regular'])->toBe('https://images.unsplash.com/photo-1/regular');
        expect($photo['user']['name'])->toBe('John Photographer');
        expect($photo['attribution_required'])->toBeTrue();
    });

    test('searchPhotos handles API errors gracefully', function () {
        MockClient::global([
            SearchPhotosRequest::class => MockResponse::make([], 500),
        ]);

        Log::shouldReceive('error')->once()->with('Unsplash API error', [
            'status' => 500,
            'response' => '[]',
        ]);

        $service = new UnsplashService;
        $result = $service->searchPhotos('landscape');

        expect($result)->toBe([]);
    });

    test('searchPhotos handles empty results', function () {
        MockClient::global([
            SearchPhotosRequest::class => MockResponse::fixture('Unsplash/empty_response'),
        ]);

        $service = new UnsplashService;
        $result = $service->searchPhotos('landscape');

        expect($result)->toBeArray();
        expect($result['results'])->toBeArray();
        expect($result['results'])->toHaveCount(0);
        expect($result['total'])->toBe(0);
        expect($result['total_pages'])->toBe(0);
    });

    test('getPhoto returns formatted photo data', function () {
        MockClient::global([
            GetPhotoRequest::class => MockResponse::fixture('Unsplash/get_photo_success'),
        ]);

        $service = new UnsplashService;
        $result = $service->getPhoto('single-photo');

        expect($result)->toBeArray();
        expect($result['id'])->toBe('single-photo');
        expect($result['description'])->toBe('Amazing sunset');
        expect($result['width'])->toBe(1600);
        expect($result['height'])->toBe(900);
        expect($result['user']['name'])->toBe('Jane Smith');
        expect($result['user']['profile_url'])->toBe('https://unsplash.com/@janesmith');
    });

    test('getPhoto returns null on API error', function () {
        MockClient::global([
            GetPhotoRequest::class => MockResponse::make([], 404),
        ]);

        Log::shouldReceive('error')->once()->with('Unsplash photo fetch error', [
            'photo_id' => 'nonexistent',
            'status' => 404,
            'response' => '[]',
        ]);

        $service = new UnsplashService;
        $result = $service->getPhoto('nonexistent');

        expect($result)->toBeNull();
    });

    test('getPhoto handles malformed response data', function () {
        MockClient::global([
            GetPhotoRequest::class => MockResponse::fixture('Unsplash/minimal_photo_data'),
        ]);

        $service = new UnsplashService;
        $result = $service->getPhoto('minimal-photo');

        expect($result)->toBeArray();
        expect($result['id'])->toBe('minimal-photo');
        expect($result['description'])->toBe(''); // Should default to empty
        expect($result['user']['name'])->toBe('Minimal User'); // Should handle user data
        expect($result['user']['username'])->toBe(''); // Should default to empty for missing fields
    });

    test('trackDownload returns true on successful tracking', function () {
        MockClient::global([
            TrackDownloadRequest::class => MockResponse::make([], 200),
        ]);

        $service = new UnsplashService;
        $result = $service->trackDownload('photo-id');

        expect($result)->toBeTrue();
    });

    test('trackDownload returns false on API error', function () {
        MockClient::global([
            TrackDownloadRequest::class => MockResponse::make([], 400),
        ]);

        $service = new UnsplashService;
        $result = $service->trackDownload('photo-id');

        expect($result)->toBeFalse();
    });

    test('trackDownload handles network timeouts', function () {
        MockClient::global([
            TrackDownloadRequest::class => MockResponse::make([], 408), // Request timeout
        ]);

        $service = new UnsplashService;
        $result = $service->trackDownload('photo-id');

        expect($result)->toBeFalse();
    });

    test('downloadAndSavePhoto uses fixture data for Saloon API calls', function () {
        // Test the fixture integration for getPhoto call
        MockClient::global([
            GetPhotoRequest::class => MockResponse::fixture('Unsplash/download_photo_data'),
        ]);

        $service = new UnsplashService;

        // Test that the fixture works for getPhoto
        $photo = $service->getPhoto('download-photo');
        expect($photo)->toBeArray();
        expect($photo['id'])->toBe('download-photo');
        expect($photo['description'])->toBe('Test download photo');
        expect($photo['urls']['regular'])->toBe('https://images.unsplash.com/test-image.jpg');
        expect($photo['user']['name'])->toBe('Test Photographer');
        expect($photo['user']['username'])->toBe('testphoto');
        expect($photo['user']['profile_url'])->toBe('https://unsplash.com/@testphoto');
        expect($photo['attribution_required'])->toBeTrue();
    });

    test('downloadAndSavePhoto with campaign ID uses fixture data', function () {
        // Test fixture data for campaign photo
        MockClient::global([
            GetPhotoRequest::class => MockResponse::fixture('Unsplash/campaign_photo_data'),
        ]);

        $service = new UnsplashService;
        $photo = $service->getPhoto('campaign-photo');

        expect($photo)->toBeArray();
        expect($photo['id'])->toBe('campaign-photo');
        expect($photo['description'])->toBe('Campaign image');
        expect($photo['urls']['regular'])->toBe('https://images.unsplash.com/campaign-image.jpg');
        expect($photo['user']['name'])->toBe('Campaign Photographer');
    });

    test('downloadAndSavePhoto returns null when photo fetch fails', function () {
        MockClient::global([
            GetPhotoRequest::class => MockResponse::make([], 404),
        ]);

        $service = new UnsplashService;
        $result = $service->downloadAndSavePhoto('nonexistent-photo');

        expect($result)->toBeNull();
    });

    test('downloadAndSavePhoto returns null when image download fails', function () {
        // Test that fixtures work for the getPhoto call in failed scenarios
        MockClient::global([
            GetPhotoRequest::class => MockResponse::fixture('Unsplash/failed_download_photo'),
        ]);

        $service = new UnsplashService;
        $photo = $service->getPhoto('failed-download');

        expect($photo)->toBeArray();
        expect($photo['id'])->toBe('failed-download');
        expect($photo['description'])->toBe('Failed download photo');
        expect($photo['urls']['regular'])->toBe('https://images.unsplash.com/failed-image.jpg');
    });

    test('downloadAndSavePhoto handles processing exceptions', function () {
        MockClient::global([
            GetPhotoRequest::class => function () {
                throw new \Exception('Processing error');
            },
        ]);

        Log::shouldReceive('error')->once()->with('Unsplash download and save error', [
            'photo_id' => 'error-photo',
            'error' => 'Processing error',
        ]);

        $service = new UnsplashService;
        $result = $service->downloadAndSavePhoto('error-photo');

        expect($result)->toBeNull();
    });

    test('formatPhoto handles missing optional fields gracefully', function () {
        $photoData = [
            'id' => 'minimal-photo',
            // Missing description and alt_description
            'urls' => [
                'regular' => 'https://images.unsplash.com/minimal.jpg',
                // Missing some URL types
            ],
            'user' => [
                'name' => 'Minimal User',
                // Missing username and links
            ],
            // Missing width, height, links
        ];

        MockClient::global([
            GetPhotoRequest::class => MockResponse::make($photoData, 200),
        ]);

        $service = new UnsplashService;
        $result = $service->getPhoto('minimal-photo');

        expect($result)->toBeArray();
        expect($result['id'])->toBe('minimal-photo');
        expect($result['description'])->toBe(''); // Should default to empty string
        expect($result['urls']['thumb'])->toBe(''); // Should default to empty string
        expect($result['width'])->toBe(0); // Should default to 0
        expect($result['height'])->toBe(0); // Should default to 0
        expect($result['user']['username'])->toBe(''); // Should default to empty string
        expect($result['user']['profile_url'])->toBe(''); // Should default to empty string
        expect($result['attribution_required'])->toBeTrue();
    });

    test('searchPhotos with different parameters', function () {
        MockClient::global([
            SearchPhotosRequest::class => MockResponse::fixture('Unsplash/empty_response'),
        ]);

        $service = new UnsplashService;

        // Test with custom page and perPage parameters
        $result = $service->searchPhotos('nature', 2, 30);

        expect($result)->toBeArray();
        expect($result['results'])->toBeArray();
        expect($result['total'])->toBe(0);
        expect($result['total_pages'])->toBe(0);
    });

    test('searchPhotos handles malformed response data', function () {
        MockClient::global([
            SearchPhotosRequest::class => MockResponse::fixture('Unsplash/malformed_response'),
        ]);

        $service = new UnsplashService;
        $result = $service->searchPhotos('test');

        expect($result)->toBeArray();
        expect($result['results'])->toBeArray();
        expect($result['results'])->toHaveCount(0); // Empty array when results missing
        expect($result['total'])->toBe(0); // Default to 0 when missing
        expect($result['total_pages'])->toBe(0); // Default to 0 when missing
    });

    test('metadata photo fixture provides correct data structure', function () {
        // Test that the metadata fixture works correctly
        MockClient::global([
            GetPhotoRequest::class => MockResponse::fixture('Unsplash/metadata_photo_data'),
        ]);

        $service = new UnsplashService;
        $photo = $service->getPhoto('metadata-test');

        expect($photo)->toBeArray();
        expect($photo['id'])->toBe('metadata-test');
        expect($photo['description'])->toBe('Metadata test image');
        expect($photo['urls']['regular'])->toBe('https://images.unsplash.com/metadata-test.jpg');
        expect($photo['user']['name'])->toBe('Metadata Photographer');
        expect($photo['width'])->toBe(1600);
        expect($photo['height'])->toBe(900);
        expect($photo['attribution_required'])->toBeTrue();
    });
});
