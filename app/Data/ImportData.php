<?php

namespace App\Data;

use App\Models\Import;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ImportData extends Data
{
    public function __construct(
        public int $id,
        public string $filename,
        public string $original_filename,
        public string $status,
        public ?int $newsletter_list_id,
        /** @var NewListData[]|null */
        public ?array $new_list_data,
        public int $total_rows,
        public int $processed_rows,
        public int $successful_rows,
        public int $failed_rows,
        /** @var ImportErrorData[]|null */
        public ?array $errors,
        public ?string $started_at,
        public ?string $completed_at,
        public int $progress_percentage,
        public ?NewsletterListData $newsletter_list = null,
    ) {}

    public static function fromModel(Import $import): self
    {
        return new self(
            id: $import->id,
            filename: $import->filename,
            original_filename: $import->original_filename,
            status: $import->status,
            newsletter_list_id: $import->newsletter_list_id,
            new_list_data: $import->new_list_data,
            total_rows: $import->total_rows ?? 0,
            processed_rows: $import->processed_rows ?? 0,
            successful_rows: $import->successful_rows ?? 0,
            failed_rows: $import->failed_rows ?? 0,
            errors: $import->errors,
            started_at: $import->started_at?->toISOString(),
            completed_at: $import->completed_at?->toISOString(),
            progress_percentage: $import->progress_percentage,
            newsletter_list: $import->relationLoaded('newsletterList') && $import->newsletterList
                ? NewsletterListData::from($import->newsletterList)
                : null,
        );
    }
}
