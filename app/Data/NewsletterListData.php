<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class NewsletterListData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $shortcode,
        public ?string $description,
        public string $from_email,
        public string $from_name,
        /** @var array<NewsletterSubscriberData> */
        public array $subscribers = [],
        public int $subscribers_count = 0,
    ) {}
}
