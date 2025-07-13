<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

class NewsletterSubscriberQueryBuilder extends Builder
{
    public function whereSubscribed(): self
    {
        return $this->where('status', 'subscribed');
    }

    public function whereUnsubscribed(): self
    {
        return $this->where('status', 'unsubscribed');
    }

    public function whereStatus(string $status): self
    {
        return $this->where('status', $status);
    }

    public function whereVerified(): self
    {
        return $this->whereNotNull('email_verified_at');
    }

    public function whereUnverified(): self
    {
        return $this->whereNull('email_verified_at');
    }

    public function whereEmail(string $email): self
    {
        return $this->where('email', $email);
    }

    public function whereNewsletterList(int $listId): self
    {
        return $this->where('newsletter_list_id', $listId);
    }

    public function countByStatus(): array
    {
        return [
            'subscribed' => $this->getModel()->newQuery()->whereSubscribed()->count(),
            'unsubscribed' => $this->getModel()->newQuery()->whereUnsubscribed()->count(),
        ];
    }

    public function subscribedAfter($date): self
    {
        return $this->where('subscribed_at', '>=', $date);
    }

    public function subscribedBefore($date): self
    {
        return $this->where('subscribed_at', '<=', $date);
    }

    public function unsubscribedAfter($date): self
    {
        return $this->where('unsubscribed_at', '>=', $date);
    }

    public function unsubscribedBefore($date): self
    {
        return $this->where('unsubscribed_at', '<=', $date);
    }

    public function orderByLatest(): self
    {
        return $this->orderBy('created_at', 'desc');
    }

    public function orderByOldest(): self
    {
        return $this->orderBy('created_at', 'asc');
    }

    public function orderBySubscribedAt(): self
    {
        return $this->orderBy('subscribed_at', 'desc');
    }

    public function searchByName(string $search): self
    {
        return $this->where(function ($query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
