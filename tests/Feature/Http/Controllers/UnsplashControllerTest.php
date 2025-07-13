<?php

use App\Models\User;
use App\Services\UnsplashService;
use Mockery;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    // Mock the UnsplashService
    $this->unsplashService = Mockery::mock(UnsplashService::class);
    $this->app->instance(UnsplashService::class, $this->unsplashService);
});

afterEach(function () {
    Mockery::close();
});

describe('search photos', function () {
    test('searches for photos with valid query', function () {
        $expectedResults = [
            'results' => [
                ['id' => 'photo1', 'urls' => ['regular' => 'https://example.com/photo1.jpg']],
                ['id' => 'photo2', 'urls' => ['regular' => 'https://example.com/photo2.jpg']],
            ],
            'total' => 2,
            'total_pages' => 1,
        ];

        $this->unsplashService
            ->shouldReceive('searchPhotos')
            ->with('nature', 1, 20)
            ->once()
            ->andReturn($expectedResults);

        $response = $this->getJson(route('unsplash.search') . '?query=nature');

        $response->assertOk()
            ->assertJson($expectedResults);
    });

    test('searches with custom pagination parameters', function () {
        $expectedResults = [
            'results' => [],
            'total' => 100,
            'total_pages' => 10,
        ];

        $this->unsplashService
            ->shouldReceive('searchPhotos')
            ->with('landscape', 2, 10)
            ->once()
            ->andReturn($expectedResults);

        $response = $this->getJson(route('unsplash.search') . '?query=landscape&page=2&per_page=10');

        $response->assertOk()
            ->assertJson($expectedResults);
    });

    test('validates required query parameter', function () {
        $response = $this->getJson(route('unsplash.search'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    });

    test('validates query minimum length', function () {
        $response = $this->getJson(route('unsplash.search') . '?query=');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    });

    test('validates query maximum length', function () {
        $longQuery = str_repeat('a', 256);
        
        $response = $this->getJson(route('unsplash.search') . '?query=' . $longQuery);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    });

    test('validates page parameter range', function () {
        $response = $this->getJson(route('unsplash.search') . '?query=nature&page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);

        $response = $this->getJson(route('unsplash.search') . '?query=nature&page=51');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page']);
    });

    test('validates per_page parameter range', function () {
        $response = $this->getJson(route('unsplash.search') . '?query=nature&per_page=0');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        $response = $this->getJson(route('unsplash.search') . '?query=nature&per_page=31');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    });

    test('uses default pagination when not provided', function () {
        $expectedResults = ['results' => []];

        $this->unsplashService
            ->shouldReceive('searchPhotos')
            ->with('test', 1, 20) // Default values
            ->once()
            ->andReturn($expectedResults);

        $response = $this->getJson(route('unsplash.search') . '?query=test');

        $response->assertOk();
    });

    test('handles special characters in search query', function () {
        $query = 'coffee & tea';
        $expectedResults = ['results' => []];

        $this->unsplashService
            ->shouldReceive('searchPhotos')
            ->with($query, 1, 20)
            ->once()
            ->andReturn($expectedResults);

        $response = $this->getJson(route('unsplash.search') . '?query=' . urlencode($query));

        $response->assertOk();
    });

    test('requires authentication', function () {
        auth()->logout();

        $response = $this->getJson(route('unsplash.search') . '?query=nature');

        $response->assertUnauthorized();
    });
});

describe('download photo', function () {
    test('downloads and saves photo successfully', function () {
        $expectedResult = [
            'success' => true,
            'id' => 'image123',
            'path' => 'campaign-images/unsplash_photo.jpg',
            'url' => '/storage/campaign-images/unsplash_photo.jpg',
        ];

        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with('photo123', null)
            ->once()
            ->andReturn($expectedResult);

        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => 'photo123'
        ]);

        $response->assertOk()
            ->assertJson($expectedResult);
    });

    test('downloads photo for specific campaign', function () {
        $campaignId = 456;
        $expectedResult = [
            'success' => true,
            'id' => 'image123',
            'path' => "campaign-images/campaign-{$campaignId}/unsplash_photo.jpg",
            'url' => "/storage/campaign-images/campaign-{$campaignId}/unsplash_photo.jpg",
        ];

        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with('photo123', $campaignId)
            ->once()
            ->andReturn($expectedResult);

        $response = $this->postJson(route('campaigns.unsplash.download', $campaignId), [
            'photo_id' => 'photo123'
        ]);

        $response->assertOk()
            ->assertJson($expectedResult);
    });

    test('validates required photo_id parameter', function () {
        $response = $this->postJson(route('unsplash.download'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo_id']);
    });

    test('validates photo_id is string', function () {
        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => 123
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo_id']);
    });

    test('handles download failure', function () {
        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with('invalid_photo', null)
            ->once()
            ->andReturn(null);

        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => 'invalid_photo'
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment([
                'error' => 'Failed to download and save image'
            ]);
    });

    test('handles null result from service', function () {
        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with('photo123', null)
            ->once()
            ->andReturn(null);

        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => 'photo123'
        ]);

        $response->assertStatus(500)
            ->assertJsonFragment([
                'error' => 'Failed to download and save image'
            ]);
    });

    test('passes campaign parameter correctly', function () {
        $campaignId = 789;

        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with('photo123', $campaignId)
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->postJson(route('campaigns.unsplash.download', $campaignId), [
            'photo_id' => 'photo123'
        ]);

        $response->assertOk();
    });

    test('handles empty string photo_id', function () {
        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo_id']);
    });

    test('handles long photo_id', function () {
        $longPhotoId = str_repeat('a', 1000);
        
        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with($longPhotoId, null)
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => $longPhotoId
        ]);

        $response->assertOk();
    });

    test('requires authentication', function () {
        auth()->logout();

        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => 'photo123'
        ]);

        $response->assertUnauthorized();
    });

    test('handles special characters in photo_id', function () {
        $photoId = 'photo-123_test.special';
        
        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with($photoId, null)
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => $photoId
        ]);

        $response->assertOk();
    });

    test('returns exact service response on success', function () {
        $serviceResponse = [
            'success' => true,
            'id' => 'db_image_id_123',
            'path' => 'campaign-images/photo.jpg',
            'url' => '/storage/campaign-images/photo.jpg',
            'full_url' => 'http://localhost/storage/campaign-images/photo.jpg',
            'width' => 1920,
            'height' => 1080,
        ];

        $this->unsplashService
            ->shouldReceive('downloadAndSavePhoto')
            ->with('unsplash_photo_id', null)
            ->once()
            ->andReturn($serviceResponse);

        $response = $this->postJson(route('unsplash.download'), [
            'photo_id' => 'unsplash_photo_id'
        ]);

        $response->assertOk()
            ->assertExactJson($serviceResponse);
    });
});