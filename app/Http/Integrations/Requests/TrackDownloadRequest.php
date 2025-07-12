<?php

namespace App\Http\Integrations\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class TrackDownloadRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(protected string $photoId) {}

    public function resolveEndpoint(): string
    {
        return '/photos/' . $this->photoId . '/download';
    }
}
