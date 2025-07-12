<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function ($subscriber) {
            if (! $subscriber->subscribed_at) {
                $subscriber->subscribed_at = now();
            }
            // create a verification token if it doesn't exist
            if (! $subscriber->verification_token) {
                $subscriber->verification_token = Str::uuid()->toString();
            }
            // create an unsubscribe token if it doesn't exist
            if (! $subscriber->unsubscribe_token) {
                $subscriber->unsubscribe_token = Str::uuid()->toString();
            }
        });
    }

    public function newsletterList(): BelongsTo
    {
        return $this->belongsTo(NewsletterList::class);
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: $this->email;
    }
}
