<?php

namespace App\QueryBuilders;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Builder;

class CampaignQueryBuilder extends Builder
{
    public function whereDraft(): self
    {
        return $this->where('status', Campaign::STATUS_DRAFT);
    }

    public function whereScheduled(): self
    {
        return $this->where('status', Campaign::STATUS_SCHEDULED);
    }

    public function whereSending(): self
    {
        return $this->where('status', Campaign::STATUS_SENDING);
    }

    public function whereSent(): self
    {
        return $this->where('status', Campaign::STATUS_SENT);
    }

    public function whereStatus(string $status): self
    {
        return $this->where('status', $status);
    }

    public function countByStatus(): array
    {
        return [
            'draft' => $this->getModel()->newQuery()->whereDraft()->count(),
            'scheduled' => $this->getModel()->newQuery()->whereScheduled()->count(),
            'sending' => $this->getModel()->newQuery()->whereSending()->count(),
            'sent' => $this->getModel()->newQuery()->whereSent()->count(),
        ];
    }

    public function withSubscriberCounts(): self
    {
        return $this->with(['newsletterList' => function ($query) {
            $query->withCount(['subscribers' => function ($subQuery) {
                $subQuery->where('status', 'subscribed');
            }]);
        }]);
    }

    public function whereScheduledBefore($date): self
    {
        return $this->where('scheduled_at', '<=', $date);
    }

    public function whereScheduledAfter($date): self
    {
        return $this->where('scheduled_at', '>=', $date);
    }

    public function readyToSend(): self
    {
        return $this->whereScheduled()
            ->whereScheduledBefore(now())
            ->whereNotNull('scheduled_at');
    }

    public function orderByLatest(): self
    {
        return $this->orderBy('created_at', 'desc');
    }

    public function orderByOldest(): self
    {
        return $this->orderBy('created_at', 'asc');
    }
}
