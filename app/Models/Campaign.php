<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    public function newsletterList()
    {
        return $this->belongsTo(NewsletterList::class);
    }

    public function getOpenRateAttribute()
    {
        return $this->sent_count > 0 ? round(($this->opens / $this->sent_count) * 100, 2) : 0;
    }

    public function getClickRateAttribute()
    {
        return $this->sent_count > 0 ? round(($this->clicks / $this->sent_count) * 100, 2) : 0;
    }

    public function getUnsubscribeRateAttribute()
    {
        return $this->sent_count > 0 ? round(($this->unsubscribes / $this->sent_count) * 100, 2) : 0;
    }

    public function getBounceRateAttribute()
    {
        return $this->sent_count > 0 ? round(($this->bounces / $this->sent_count) * 100, 2) : 0;
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function isSending()
    {
        return $this->status === 'sending';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function canSend()
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
