<?php

namespace App\Http\Integrations;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class UnsplashConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return config('services.unsplash.base_url');
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Client-ID ' . config('services.unsplash.access_key'),
            'Accept-Version' => 'v1',
        ];
    }
}
