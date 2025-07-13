<?php

use App\Models\Image;
use App\Models\User;

describe('Image Model', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    test('has correct fillable attributes', function () {
        $fillable = [
            'filename',
            'path',
            'url',
            'original_filename',
            'mime_type',
            'size',
            'width',
            'height',
            'alt_text',
            'user_id',
        ];

        expect((new Image())->getFillable())->toBe($fillable);
    });

    test('casts integer fields correctly', function () {
        $image = new Image([
            'size' => '1024000',
            'width' => '800',
            'height' => '600'
        ]);

        expect($image->size)->toBeInt()
            ->and($image->width)->toBeInt()
            ->and($image->height)->toBeInt()
            ->and($image->size)->toBe(1024000)
            ->and($image->width)->toBe(800)
            ->and($image->height)->toBe(600);
    });

    test('belongs to user', function () {
        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'test.jpg',
            'path' => '/uploads/test.jpg',
            'url' => 'https://example.com/uploads/test.jpg',
            'original_filename' => 'original_test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024000,
            'width' => 800,
            'height' => 600,
            'alt_text' => 'Test image',
            'user_id' => $user->id
        ]);

        expect($image->user)->toBeInstanceOf(User::class)
            ->and($image->user->id)->toBe($user->id);
    });

    test('can be created with minimal required fields', function () {
        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'minimal.jpg',
            'path' => '/uploads/minimal.jpg',
            'url' => 'https://example.com/uploads/minimal.jpg',
            'original_filename' => 'minimal.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024000,
            'user_id' => $user->id
        ]);

        expect($image->filename)->toBe('minimal.jpg')
            ->and($image->path)->toBe('/uploads/minimal.jpg')
            ->and($image->url)->toBe('https://example.com/uploads/minimal.jpg')
            ->and($image->original_filename)->toBe('minimal.jpg')
            ->and($image->mime_type)->toBe('image/jpeg')
            ->and($image->size)->toBe(1024000)
            ->and($image->user_id)->toBe($user->id);
    });

    test('handles different image types', function () {
        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'test.png',
            'path' => '/uploads/test.png',
            'url' => 'https://example.com/uploads/test.png',
            'original_filename' => 'test.png',
            'mime_type' => 'image/png',
            'size' => 2048000,
            'width' => 1200,
            'height' => 900,
            'alt_text' => 'PNG test image',
            'user_id' => $user->id
        ]);

        expect($image->mime_type)->toBe('image/png')
            ->and($image->filename)->toBe('test.png');
    });

    test('can store metadata correctly', function () {
        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'large_image.jpg',
            'path' => '/uploads/2024/large_image.jpg',
            'url' => 'https://cdn.example.com/uploads/2024/large_image.jpg',
            'original_filename' => 'My Large Photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 5242880, // 5MB
            'width' => 3840,
            'height' => 2160,
            'alt_text' => 'A beautiful landscape photo',
            'user_id' => $user->id
        ]);

        expect($image->size)->toBe(5242880)
            ->and($image->width)->toBe(3840)
            ->and($image->height)->toBe(2160)
            ->and($image->alt_text)->toBe('A beautiful landscape photo')
            ->and($image->original_filename)->toBe('My Large Photo.jpg');
    });

    test('handles null values for optional fields', function () {
        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'no_metadata.jpg',
            'path' => '/uploads/no_metadata.jpg',
            'url' => 'https://example.com/uploads/no_metadata.jpg',
            'original_filename' => 'no_metadata.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024000, // Required field
            'width' => null,
            'height' => null,
            'alt_text' => null,
            'user_id' => $user->id // Required field
        ]);

        expect($image->size)->toBe(1024000)
            ->and($image->width)->toBeNull()
            ->and($image->height)->toBeNull()
            ->and($image->alt_text)->toBeNull()
            ->and($image->user_id)->toBe($user->id);
    });

    test('user relationship is required', function () {
        // Test that creating an image without user_id fails
        expect(function () {
            Image::create([
                'filename' => 'orphan.jpg',
                'path' => '/uploads/orphan.jpg',
                'url' => 'https://example.com/uploads/orphan.jpg',
                'original_filename' => 'orphan.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 1024000,
                'user_id' => null
            ]);
        })->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('stores file path and URL separately', function () {
        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'cdn_test.jpg',
            'path' => '/local/storage/uploads/cdn_test.jpg',
            'url' => 'https://cdn.example.com/images/cdn_test.jpg',
            'original_filename' => 'cdn_test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024000,
            'user_id' => $user->id
        ]);

        // Path might be local storage path
        expect($image->path)->toBe('/local/storage/uploads/cdn_test.jpg')
            ->and($image->url)->toBe('https://cdn.example.com/images/cdn_test.jpg');
        // URL might be CDN or external URL
    });

    test('handles different image formats', function () {
        $formats = [
            ['filename' => 'test.jpg', 'mime_type' => 'image/jpeg'],
            ['filename' => 'test.png', 'mime_type' => 'image/png'],
            ['filename' => 'test.gif', 'mime_type' => 'image/gif'],
            ['filename' => 'test.webp', 'mime_type' => 'image/webp'],
            ['filename' => 'test.svg', 'mime_type' => 'image/svg+xml'],
        ];

        $user = User::factory()->create();
        foreach ($formats as $format) {
            $image = Image::create([
                'filename' => $format['filename'],
                'path' => '/uploads/' . $format['filename'],
                'url' => 'https://example.com/uploads/' . $format['filename'],
                'original_filename' => $format['filename'],
                'mime_type' => $format['mime_type'],
                'size' => 1024000,
                'user_id' => $user->id
            ]);

            expect($image->mime_type)->toBe($format['mime_type'])
                ->and($image->filename)->toBe($format['filename']);
        }
    });

    test('can handle very large images', function () {
        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'huge_image.jpg',
            'path' => '/uploads/huge_image.jpg',
            'url' => 'https://example.com/uploads/huge_image.jpg',
            'original_filename' => 'huge_image.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 52428800, // 50MB
            'width' => 7680,     // 8K width
            'height' => 4320,     // 8K height
            'user_id' => $user->id
        ]);

        expect($image->size)->toBe(52428800)
            ->and($image->width)->toBe(7680)
            ->and($image->height)->toBe(4320);
    });

    test('can store descriptive alt text', function () {
        $longAltText = 'This is a very detailed description of an image that includes multiple elements such as people, objects, colors, and emotions to provide comprehensive accessibility information for screen readers and other assistive technologies.';

        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'descriptive.jpg',
            'path' => '/uploads/descriptive.jpg',
            'url' => 'https://example.com/uploads/descriptive.jpg',
            'original_filename' => 'descriptive.jpg',
            'mime_type' => 'image/jpeg',
            'alt_text' => $longAltText,
            'size' => 1024000,
            'user_id' => $user->id
        ]);

        expect($image->alt_text)->toBe($longAltText);
    });

    test('preserves original filename with special characters', function () {
        $originalFilename = 'My Photo (2024) - Version #1.jpg';

        $user = User::factory()->create();
        $image = Image::create([
            'filename' => 'my_photo_2024_version_1.jpg', // Sanitized version
            'path' => '/uploads/my_photo_2024_version_1.jpg',
            'url' => 'https://example.com/uploads/my_photo_2024_version_1.jpg',
            'original_filename' => $originalFilename,
            'mime_type' => 'image/jpeg',
            'size' => 1024000,
            'user_id' => $user->id
        ]);

        expect($image->original_filename)->toBe($originalFilename)
            ->and($image->filename)->toBe('my_photo_2024_version_1.jpg');
    });
});
