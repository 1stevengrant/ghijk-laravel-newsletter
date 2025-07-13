<?php

use App\Models\Import;
use App\Models\NewsletterList;

describe('Import Model', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    test('has no guarded attributes', function () {
        expect((new Import)->getGuarded())->toBe([]);
    });

    test('casts arrays and dates correctly', function () {
        $newListData = [
            'name' => 'Test List',
            'description' => 'Test Description',
        ];
        $errors = [
            'Row 1: Invalid email',
            'Row 5: Missing data',
        ];

        $import = Import::create([
            'filename' => 'test.csv',
            'original_filename' => 'test.csv',
            'new_list_data' => $newListData,
            'errors' => $errors,
            'started_at' => '2024-01-01 10:00:00',
            'completed_at' => '2024-01-01 11:00:00',
        ]);

        expect($import->new_list_data)->toBeArray()
            ->and($import->new_list_data)->toBe($newListData)
            ->and($import->errors)->toBeArray()
            ->and($import->errors)->toBe($errors)
            ->and($import->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($import->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    test('belongs to newsletter list', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'filename' => 'test.csv',
            'original_filename' => 'test.csv',
            'newsletter_list_id' => $list->id,
        ]);

        expect($import->newsletterList)->toBeInstanceOf(NewsletterList::class)
            ->and($import->newsletterList->id)->toBe($list->id);
    });

    test('newsletter list relationship can be null', function () {
        $import = Import::create([
            'filename' => 'test.csv',
            'original_filename' => 'test.csv',
            'newsletter_list_id' => null,
        ]);

        expect($import->newsletterList)->toBeNull()
            ->and($import->newsletter_list_id)->toBeNull();
    });

    test('calculates progress percentage correctly', function () {
        // Zero total rows
        $import = Import::create([
            'filename' => 'empty.csv',
            'original_filename' => 'empty.csv',
            'total_rows' => 0,
            'processed_rows' => 0,
        ]);
        expect($import->progress_percentage)->toBe(0);

        // Test with default total rows (0)
        $import = Import::create([
            'filename' => 'default.csv',
            'original_filename' => 'default.csv',
            'processed_rows' => 5,
        ]);
        expect($import->progress_percentage)->toBe(0);

        // Partial progress
        $import = Import::create([
            'filename' => 'partial.csv',
            'original_filename' => 'partial.csv',
            'total_rows' => 100,
            'processed_rows' => 25,
        ]);
        expect($import->progress_percentage)->toBe(25);

        // Complete progress
        $import = Import::create([
            'filename' => 'complete.csv',
            'original_filename' => 'complete.csv',
            'total_rows' => 50,
            'processed_rows' => 50,
        ]);
        expect($import->progress_percentage)->toBe(100);

        // Fractional progress rounds correctly
        $import = Import::create([
            'filename' => 'fraction.csv',
            'original_filename' => 'fraction.csv',
            'total_rows' => 3,
            'processed_rows' => 1,
        ]);
        expect($import->progress_percentage)->toBe(33); // 33.33 rounded to 33
    });

    test('stores import metadata correctly', function () {
        $import = Import::create([
            'filename' => 'subscribers.csv',
            'original_filename' => 'My Subscriber List.csv',
            'status' => 'pending',
            'total_rows' => 1000,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
        ]);

        expect($import->filename)->toBe('subscribers.csv')
            ->and($import->original_filename)->toBe('My Subscriber List.csv')
            ->and($import->status)->toBe('pending')
            ->and($import->total_rows)->toBe(1000)
            ->and($import->processed_rows)->toBe(0)
            ->and($import->successful_rows)->toBe(0)
            ->and($import->failed_rows)->toBe(0);
    });

    test('handles different import statuses', function () {
        $statuses = ['pending', 'processing', 'completed', 'failed'];

        foreach ($statuses as $status) {
            $import = Import::create([
                'filename' => "test_{$status}.csv",
                'original_filename' => "test_{$status}.csv",
                'status' => $status,
            ]);

            expect($import->status)->toBe($status);
        }
    });

    test('stores progress tracking correctly', function () {
        $import = Import::create([
            'filename' => 'progress_test.csv',
            'original_filename' => 'progress_test.csv',
            'total_rows' => 500,
            'processed_rows' => 300,
            'successful_rows' => 280,
            'failed_rows' => 20,
        ]);

        expect($import->total_rows)->toBe(500)
            ->and($import->processed_rows)->toBe(300)
            ->and($import->successful_rows)->toBe(280)
            ->and($import->failed_rows)->toBe(20)
            ->and($import->progress_percentage)->toBe(60);
        // 300/500 * 100
    });

    test('stores error messages as array', function () {
        $errors = [
            'Row 1: Invalid email format for "not-an-email"',
            'Row 5: Missing required field "email"',
            'Row 12: Duplicate email address',
            'Row 18: Email domain not allowed',
        ];

        $import = Import::create([
            'filename' => 'errors.csv',
            'original_filename' => 'errors.csv',
            'errors' => $errors,
            'failed_rows' => count($errors),
        ]);

        expect($import->errors)->toBeArray()
            ->and($import->errors)->toBe($errors)
            ->and(count($import->errors))->toBe(4)
            ->and($import->failed_rows)->toBe(4);
    });

    test('stores new list data for creating lists during import', function () {
        $newListData = [
            'name' => 'Marketing Newsletter',
            'description' => 'Monthly marketing updates',
            'from_email' => 'marketing@example.com',
            'from_name' => 'Marketing Team',
        ];

        $import = Import::create([
            'filename' => 'new_list.csv',
            'original_filename' => 'new_list.csv',
            'newsletter_list_id' => null,
            'new_list_data' => $newListData,
        ]);

        expect($import->new_list_data)->toBeArray()
            ->and($import->new_list_data)->toBe($newListData)
            ->and($import->newsletter_list_id)->toBeNull();
    });

    test('handles timing fields correctly', function () {
        $startTime = now();
        $endTime = now()->addMinutes(5);

        $import = Import::create([
            'filename' => 'timing.csv',
            'original_filename' => 'timing.csv',
            'started_at' => $startTime,
            'completed_at' => $endTime,
        ]);

        expect($import->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($import->completed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
            ->and($import->started_at->toDateTimeString())->toBe($startTime->toDateTimeString())
            ->and($import->completed_at->toDateTimeString())->toBe($endTime->toDateTimeString());
    });

    test('can be created with minimal data', function () {
        $import = Import::create([
            'filename' => 'minimal.csv',
            'original_filename' => 'minimal.csv',
        ]);

        expect($import->filename)->toBe('minimal.csv')
            ->and($import->original_filename)->toBe('minimal.csv')
            ->and($import->newsletter_list_id)->toBeNull()
            ->and($import->new_list_data)->toBeNull()
            ->and($import->errors)->toBeNull()
            ->and($import->started_at)->toBeNull()
            ->and($import->completed_at)->toBeNull();
    });

    test('progress percentage handles edge cases', function () {
        // More processed than total (shouldn't happen but let's handle it)
        $import = Import::create([
            'filename' => 'edge.csv',
            'original_filename' => 'edge.csv',
            'total_rows' => 100,
            'processed_rows' => 150,
        ]);
        expect($import->progress_percentage)->toBe(150);

        // Very large numbers
        $import = Import::create([
            'filename' => 'large.csv',
            'original_filename' => 'large.csv',
            'total_rows' => 1000000,
            'processed_rows' => 333333,
        ]);
        expect($import->progress_percentage)->toBe(33); // 33.3333 rounded to 33
    });

    test('stores complex error data', function () {
        $complexErrors = [
            'Row 1: ValidationException - The email field is required.',
            'Row 3: Duplicate subscriber found in list "Marketing Updates"',
            'Row 7: Email "invalid@domain" failed format validation',
            'Row 15: Database constraint violation - subscriber limit reached',
        ];

        $import = Import::create([
            'filename' => 'complex_errors.csv',
            'original_filename' => 'complex_errors.csv',
            'status' => 'completed',
            'total_rows' => 20,
            'processed_rows' => 20,
            'successful_rows' => 16,
            'failed_rows' => 4,
            'errors' => $complexErrors,
        ]);

        expect($import->errors)->toHaveCount(4)
            ->and($import->errors[0])->toContain('ValidationException')
            ->and($import->errors[1])->toContain('Duplicate subscriber')
            ->and($import->successful_rows + $import->failed_rows)->toBe($import->processed_rows);
    });
});
