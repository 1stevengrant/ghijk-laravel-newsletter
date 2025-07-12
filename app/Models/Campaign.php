<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\QueryBuilders\CampaignQueryBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use HasFactory;

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'blocks' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->shortcode)) {
                $campaign->shortcode = self::generateShortcode();
            }
        });
    }

    private static function generateShortcode(): string
    {
        do {
            $shortcode = mb_substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        } while (self::where('shortcode', $shortcode)->exists());

        return $shortcode;
    }

    public function newEloquentBuilder($query): CampaignQueryBuilder
    {
        return new CampaignQueryBuilder($query);
    }

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
        return $this->status === self::STATUS_DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function canEdit(): bool
    {
        return ! $this->isSent();
    }

    public function canDelete(): bool
    {
        return $this->isDraft();
    }

    public function canSend(): bool
    {
        if (! in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED])) {
            return false;
        }

        // Check if the list has any subscribed subscribers
        $subscriberCount = $this->newsletterList->subscribers()
            ->where('status', 'subscribed')
            ->count();

        return $subscriberCount > 0;
    }

    public function getSentAtFriendlyAttribute(): string
    {
        if (! $this->sent_at) {
            return 'N/A';
        }

        return $this->sent_at->diffForHumans();
    }

    public function getScheduledAtFriendlyAttribute(): string
    {
        if (! $this->scheduled_at) {
            return 'N/A';
        }

        return $this->scheduled_at->diffForHumans();
    }
}
