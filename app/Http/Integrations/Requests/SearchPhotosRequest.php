<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchPhotosRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $searchQuery,
        protected int $page = 1,
        protected int $perPage = 20,
        protected string $orderBy = 'relevant'
    ) {}

    public function resolveEndpoint(): string
    {
        return '/search/photos';
    }

    protected function defaultQuery(): array
    {
        return [
            'query' => $this->searchQuery,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'order_by' => $this->orderBy,
        ];
    }
}
