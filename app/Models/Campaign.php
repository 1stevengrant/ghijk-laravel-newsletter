<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function newsletterList(): BelongsTo
    {
        return $this->belongsTo(NewsletterList::class);
    }

    public function getOpenRateAttribute(): float|int
    {
        return $this->sent_count > 0 ? round(($this->opens / $this->sent_count) * 100, 2) : 0;
    }

    public function getClickRateAttribute(): float|int
    {
        return $this->sent_count > 0 ? round(($this->clicks / $this->sent_count) * 100, 2) : 0;
    }

    public function getUnsubscribeRateAttribute(): float|int
    {
        return $this->sent_count > 0 ? round(($this->unsubscribes / $this->sent_count) * 100, 2) : 0;
    }

    public function getBounceRateAttribute(): float|int
    {
        return $this->sent_count > 0 ? round(($this->bounces / $this->sent_count) * 100, 2) : 0;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function canSend(): bool
    {
        if (! in_array($this->status, ['draft', 'scheduled'])) {
            return false;
        }

        // Check if the list has any subscribed subscribers
        $subscriberCount = $this->newsletterList->subscribers()
            ->where('status', 'subscribed')
            ->count();

        return $subscriberCount > 0;
    }
}
