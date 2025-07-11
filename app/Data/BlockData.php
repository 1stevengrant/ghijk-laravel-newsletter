<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BlockData extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $content,
        public ?BlockSettingsData $settings = null,
    ) {}
}