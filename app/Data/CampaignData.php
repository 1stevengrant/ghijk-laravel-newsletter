<?php

namespace App\Data;

use App\Models\Campaign;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CampaignData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $subject,
        public ?string $content,
        public int $newsletter_list_id,
        public string $status,
        public ?string $scheduled_at,
        public ?string $scheduled_at_friendly,
        public ?string $sent_at,
        public ?string $sent_at_friendly,
        public int $sent_count,
        public int $opens,
        public int $clicks,
        public int $unsubscribes,
        public int $bounces,
        public float $open_rate,
        public float $click_rate,
        public float $unsubscribe_rate,
        public float $bounce_rate,
        public bool $can_send,
        public bool $can_edit,
        public bool $can_delete,
        /** @var BlockData[]|null */
        public ?array $blocks = null,
        public ?NewsletterListData $newsletter_list = null,
    ) {}

    public static function fromModel(Campaign $campaign): self
    {
        // Calculate can_send, can_edit, and can_delete based on the campaign model
        $canSend = $campaign->canSend();
        $canEdit = $campaign->canEdit();
        $canDelete = $campaign->canDelete();

        return new self(
            id: $campaign->id,
            name: $campaign->name,
            subject: $campaign->subject,
            content: $campaign->content,
            newsletter_list_id: $campaign->newsletter_list_id,
            status: $campaign->status,
            scheduled_at: $campaign->scheduled_at?->toISOString(),
            scheduled_at_friendly: $campaign->scheduled_at_friendly,
            sent_at: $campaign->sent_at?->toISOString(),
            sent_at_friendly: $campaign->sent_at_friendly,
            sent_count: $campaign->sent_count,
            opens: $campaign->opens,
            clicks: $campaign->clicks,
            unsubscribes: $campaign->unsubscribes,
            bounces: $campaign->bounces,
            open_rate: $campaign->open_rate,
            click_rate: $campaign->click_rate,
            unsubscribe_rate: $campaign->unsubscribe_rate,
            bounce_rate: $campaign->bounce_rate,
            can_send: $canSend,
            can_edit: $canEdit,
            can_delete: $canDelete,
            blocks: $campaign->blocks ? BlockData::collect($campaign->blocks) : null,
            newsletter_list: $campaign->relationLoaded('newsletterList') ?
                NewsletterListData::from($campaign->newsletterList) : null,
        );
    }
}
