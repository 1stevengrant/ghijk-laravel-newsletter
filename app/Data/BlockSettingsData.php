<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BlockSettingsData extends Data
{
    public function __construct(
        public ?int $imageId = null,
        public ?string $imageUrl = null,
        public ?string $imageAlt = null,
        public ?string $imagePath = null,
        public ?string $listType = null,
        public ?string $quoteAuthor = null,
    ) {}
}