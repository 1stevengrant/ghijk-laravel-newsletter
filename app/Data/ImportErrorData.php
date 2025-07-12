<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ImportErrorData extends Data
{
    public function __construct(
        public int $row,
        public string $message,
        public ?string $email = null,
    ) {}
}