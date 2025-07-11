<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class NewsletterSubscriberData extends Data
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $subscribed_at = null,
        public ?string $verification_token = null,
    ) {}
}
