<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class NewsletterListsData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $from_email,
        public string $from_name
    ) {}
}
