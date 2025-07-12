<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\NewsletterList;
use App\Events\ImportCompleted;
use App\Models\NewsletterSubscriber;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, IsMonitored, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public Import $import
    ) {
        //
    }

    public function handle(): void
    {
        $previousStatus = $this->import->status;

        try {
            // Update status to processing
            $this->import->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Read CSV file
            $filePath = Storage::disk('local')->path('imports/' . $this->import->filename);

            if (! file_exists($filePath)) {
                // Log debug info
                \Log::error('Import file not found', [
                    'expected_path' => $filePath,
                    'filename' => $this->import->filename,
                    'storage_path' => storage_path('app/imports/'),
                    'files_in_dir' => Storage::disk('local')->files('imports'),
                ]);
                throw new \Exception('Import file not found at: ' . $filePath);
            }

            $handle = fopen($filePath, 'r');
            if (! $handle) {
                throw new \Exception('Could not open import file');
            }

            // Get or create newsletter list
            $newsletterList = $this->getOrCreateNewsletterList();

            // Read header row
            $header = fgetcsv($handle);
            if (! $header) {
                throw new \Exception('Invalid CSV file - no header row');
            }

            // Remove BOM from first header if present
            if (isset($header[0])) {
                $header[0] = str_replace("\xEF\xBB\xBF", '', $header[0]);
            }

            // Process data rows
            $totalRows = 0;
            $processedRows = 0;
            $successfulRows = 0;
            $failedRows = 0;
            $errors = [];

            // Count total rows first
            while (($row = fgetcsv($handle)) !== false) {
                $totalRows++;
            }

            // Update total rows
            $this->import->update(['total_rows' => $totalRows]);

            // Reset file pointer
            rewind($handle);
            fgetcsv($handle); // Skip header again

            // Process each row
            while (($row = fgetcsv($handle)) !== false) {
                $processedRows++;

                try {
                    if (count($header) !== count($row)) {
                        $errors[] = "Row {$processedRows}: Column count mismatch";
                        $failedRows++;

                        continue;
                    }

                    $data = array_combine($header, $row);

                    // Extract subscriber data
                    $email = $data['email'] ?? $data['Email'] ?? null;
                    $firstName = $data['first_name'] ?? $data['First Name'] ?? $data['firstname'] ?? null;
                    $lastName = $data['last_name'] ?? $data['Last Name'] ?? $data['lastname'] ?? null;

                    if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Row {$processedRows}: Invalid email '{$email}'";
                        $failedRows++;

                        continue;
                    }

                    // Check if subscriber already exists for this list
                    $subscriber = NewsletterSubscriber::firstOrCreate(
                        [
                            'email' => $email,
                            'newsletter_list_id' => $newsletterList->id,
                        ],
                        [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'status' => 'subscribed',
                            'newsletter_list_id' => $newsletterList->id,
                        ]
                    );

                    $successfulRows++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$processedRows}: " . $e->getMessage();
                    $failedRows++;
                }

                // Update progress periodically (every 100 rows or 10% progress)
                $shouldUpdate = $processedRows % 100 === 0;
                $progressPercentage = $totalRows > 0 ? (int) round(($processedRows / $totalRows) * 100) : 0;
                $previousProgressPercentage = $this->import->total_rows > 0 ? (int) round(($this->import->processed_rows / $this->import->total_rows) * 100) : 0;

                // Also update if we've hit a new 10% milestone
                if (! $shouldUpdate && floor($progressPercentage / 10) > floor($previousProgressPercentage / 10)) {
                    $shouldUpdate = true;
                }

                if ($shouldUpdate) {
                    $this->import->update([
                        'processed_rows' => $processedRows,
                        'successful_rows' => $successfulRows,
                        'failed_rows' => $failedRows,
                        'errors' => array_slice($errors, 0, 50), // Limit errors to 50 to prevent database bloat
                    ]);

                    // No broadcasting during processing - only on completion/failure
                }
            }

            fclose($handle);

            // Final update
            $this->import->update([
                'status' => 'completed',
                'processed_rows' => $processedRows,
                'successful_rows' => $successfulRows,
                'failed_rows' => $failedRows,
                'errors' => array_slice($errors, 0, 50), // Limit errors to keep payload manageable
                'completed_at' => now(),
            ]);

            ImportCompleted::dispatch($this->import);

            // Clean up file
            Storage::disk('local')->delete('imports/' . $this->import->filename);

        } catch (\Exception $e) {
            $this->import->update([
                'status' => 'failed',
                'errors' => [
                    'Processing failed: ' . $e->getMessage(),
                ],
                'completed_at' => now(),
            ]);

            ImportCompleted::dispatch($this->import);

            // Clean up file if it exists
            if (Storage::disk('local')->exists('imports/' . $this->import->filename)) {
                Storage::disk('local')->delete('imports/' . $this->import->filename);
            }

            throw $e;
        }
    }

    private function getOrCreateNewsletterList(): NewsletterList
    {
        if ($this->import->newsletter_list_id) {
            return NewsletterList::findOrFail($this->import->newsletter_list_id);
        }

        if ($this->import->new_list_data) {
            return NewsletterList::create($this->import->new_list_data);
        }

        throw new \Exception('No newsletter list specified');
    }
}
