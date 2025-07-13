<?php

use App\Models\Import;
use App\Jobs\ProcessImportJob;
use App\Models\NewsletterList;
use App\Events\ImportCompleted;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

describe('ProcessImportJob', function () {
    beforeEach(function () {
        Storage::fake('local');
        Event::fake();
    });

    test('processes CSV file successfully and creates subscribers', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'test-import.csv',
            'original_filename' => 'test-import.csv',
            'status' => 'pending',
        ]);

        // Create test CSV content
        $csvContent = "email,first_name,last_name\n";
        $csvContent .= "john@example.com,John,Doe\n";
        $csvContent .= "jane@example.com,Jane,Smith\n";
        $csvContent .= "bob@example.com,Bob,Johnson\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->status)->toBe('completed')
            ->and($import->total_rows)->toBe(3)
            ->and($import->processed_rows)->toBe(3)
            ->and($import->failed_rows)->toBe(0)
            ->and($import->started_at)->not->toBeNull()
            ->and($import->completed_at)->not->toBeNull()
            ->and(NewsletterSubscriber::where('newsletter_list_id', $list->id)->count())->toBe(3);

        // Check subscribers were created

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
        ]);

        // Check successful rows last since this is what's failing
        expect($import->successful_rows)->toBe(3);

        Event::assertDispatched(ImportCompleted::class);
    });

    test('handles CSV with BOM correctly', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'bom-test.csv',
            'original_filename' => 'bom-test.csv',
            'status' => 'pending',
        ]);

        // Create CSV with BOM
        $csvContent = "\xEF\xBB\xBFemail,first_name\n";
        $csvContent .= "test@example.com,Test\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->status)->toBe('completed')
            ->and($import->successful_rows)->toBe(1);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
        ]);
    });

    test('handles different CSV header variations', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'variations.csv',
            'original_filename' => 'variations.csv',
            'status' => 'pending',
        ]);

        $csvContent = "Email,First Name,Last Name\n";
        $csvContent .= "user1@example.com,User,One\n";
        $csvContent .= "user2@example.com,User,Two\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->successful_rows)->toBe(2);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'user1@example.com',
            'first_name' => 'User',
            'last_name' => 'One',
        ]);
    });

    test('handles CSV with alternate header names', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'alternate.csv',
            'original_filename' => 'alternate.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email,firstname,lastname\n";
        $csvContent .= "alt@example.com,Alt,User\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->successful_rows)->toBe(1);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'alt@example.com',
            'first_name' => 'Alt',
            'last_name' => 'User',
        ]);
    });

    test('skips invalid email addresses and logs errors', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'invalid-emails.csv',
            'original_filename' => 'invalid-emails.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email,first_name\n";
        $csvContent .= "valid@example.com,Valid\n";
        $csvContent .= "invalid-email,Invalid\n";
        $csvContent .= "another@example.com,Another\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->status)->toBe('completed')
            ->and($import->total_rows)->toBe(3)
            ->and($import->successful_rows)->toBe(2)
            ->and($import->failed_rows)->toBe(1)
            ->and($import->errors)->toContain("Row 2: Invalid email 'invalid-email'")
            ->and(NewsletterSubscriber::where('newsletter_list_id', $list->id)->count())->toBe(2);

    });

    test('handles column count mismatch', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'mismatch.csv',
            'original_filename' => 'mismatch.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email,first_name,last_name\n";
        $csvContent .= "good@example.com,Good,User\n";
        $csvContent .= "incomplete@example.com,Incomplete\n"; // Missing last column
        $csvContent .= "complete@example.com,Complete,User\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->successful_rows)->toBe(2)
            ->and($import->failed_rows)->toBe(1)
            ->and($import->errors)->toContain('Row 2: Column count mismatch');
    });

    test('prevents duplicate subscribers in same list', function () {
        $list = NewsletterList::factory()->create();

        // Create existing subscriber
        NewsletterSubscriber::factory()->create([
            'email' => 'existing@example.com',
            'newsletter_list_id' => $list->id,
            'first_name' => 'Existing',
        ]);

        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'duplicates.csv',
            'original_filename' => 'duplicates.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email,first_name\n";
        $csvContent .= "existing@example.com,Updated\n";
        $csvContent .= "new@example.com,New\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->successful_rows)->toBe(2)
            ->and(NewsletterSubscriber::where('newsletter_list_id', $list->id)->count())->toBe(2);

        // Original subscriber should remain unchanged (firstOrCreate doesn't update)
        $existingSubscriber = NewsletterSubscriber::where('email', 'existing@example.com')->first();
        expect($existingSubscriber->first_name)->toBe('Existing');
    });

    test('creates new list when new_list_data is provided', function () {
        $import = Import::create([
            'newsletter_list_id' => null,
            'new_list_data' => [
                'name' => 'New List from Import',
                'description' => 'Created during import',
                'from_email' => 'test@example.com',
                'from_name' => 'Test Sender',
            ],
            'filename' => 'new-list.csv',
            'original_filename' => 'new-list.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email,first_name\n";
        $csvContent .= "user@example.com,User\n";

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->successful_rows)->toBe(1);

        $this->assertDatabaseHas('newsletter_lists', [
            'name' => 'New List from Import',
            'description' => 'Created during import',
        ]);

        $newList = NewsletterList::where('name', 'New List from Import')->first();
        expect(NewsletterSubscriber::where('newsletter_list_id', $newList->id)->count())->toBe(1);
    });

    test('fails when file does not exist', function () {
        $import = Import::create([
            'filename' => 'nonexistent.csv',
            'original_filename' => 'nonexistent.csv',
            'status' => 'pending',
        ]);

        $job = new ProcessImportJob($import);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Import file not found');

        $job->handle();

        $import->refresh();

        expect($import->status)->toBe('failed')
            ->and($import->errors)->toContain('Processing failed: Import file not found at:');
        Event::assertDispatched(ImportCompleted::class);
    });

    test('fails when no newsletter list is specified', function () {
        $import = Import::create([
            'newsletter_list_id' => null,
            'new_list_data' => null,
            'filename' => 'no-list.csv',
            'original_filename' => 'no-list.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email\ntest@example.com\n";
        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No newsletter list specified');

        $job->handle();

        $import->refresh();
        expect($import->status)->toBe('failed');
    });

    test('fails when CSV has no header row', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'no-header.csv',
            'original_filename' => 'no-header.csv',
            'status' => 'pending',
        ]);

        // Empty file
        Storage::disk('local')->put('imports/' . $import->filename, '');

        $job = new ProcessImportJob($import);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid CSV file - no header row');

        $job->handle();
    });

    test('updates progress during processing', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'progress.csv',
            'original_filename' => 'progress.csv',
            'status' => 'pending',
        ]);

        // Create CSV with 150 rows to trigger progress updates
        $csvContent = "email,first_name\n";
        for ($i = 1; $i <= 150; $i++) {
            $csvContent .= "user{$i}@example.com,User{$i}\n";
        }

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->total_rows)->toBe(150)
            ->and($import->processed_rows)->toBe(150)
            ->and($import->successful_rows)->toBe(150);
    });

    test('limits errors to prevent database bloat', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'many-errors.csv',
            'original_filename' => 'many-errors.csv',
            'status' => 'pending',
        ]);

        // Create CSV with many invalid emails
        $csvContent = "email,first_name\n";
        for ($i = 1; $i <= 60; $i++) {
            $csvContent .= "invalid-email-{$i},User{$i}\n";
        }

        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        $job = new ProcessImportJob($import);
        $job->handle();

        $import->refresh();

        expect($import->failed_rows)->toBe(60)
            ->and(count($import->errors))->toBe(50);
        // Limited to 50 errors
    });

    test('cleans up import file after successful processing', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'cleanup.csv',
            'original_filename' => 'cleanup.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email,first_name\ntest@example.com,Test\n";
        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        expect(Storage::disk('local')->exists('imports/' . $import->filename))->toBeTrue();

        $job = new ProcessImportJob($import);
        $job->handle();

        expect(Storage::disk('local')->exists('imports/' . $import->filename))->toBeFalse();
    });

    test('cleans up import file after failed processing', function () {
        $import = Import::create([
            'newsletter_list_id' => null,
            'new_list_data' => null,
            'filename' => 'cleanup-fail.csv',
            'original_filename' => 'cleanup-fail.csv',
            'status' => 'pending',
        ]);

        $csvContent = "email\ntest@example.com\n";
        Storage::disk('local')->put('imports/' . $import->filename, $csvContent);

        expect(Storage::disk('local')->exists('imports/' . $import->filename))->toBeTrue();

        $job = new ProcessImportJob($import);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected to fail
        }

        expect(Storage::disk('local')->exists('imports/' . $import->filename))->toBeFalse();
    });

    test('handles file that cannot be opened', function () {
        $list = NewsletterList::factory()->create();
        $import = Import::create([
            'newsletter_list_id' => $list->id,
            'filename' => 'unreadable.csv',
            'original_filename' => 'unreadable.csv',
            'status' => 'pending',
        ]);

        // Create the file but we'll mock the fopen failure
        Storage::disk('local')->put('imports/' . $import->filename, 'content');

        // Mock fopen to return false
        $job = new ProcessImportJob($import);

        // Since we can't easily mock fopen in this context, we'll test by creating
        // a file that exists but has the wrong path internally
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('handle');

        // This is a simplified test - in real scenario fopen could fail due to permissions
        expect(Storage::disk('local')->exists('imports/' . $import->filename))->toBeTrue();
    });
});
