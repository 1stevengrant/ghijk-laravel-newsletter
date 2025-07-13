<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\QueryBuilders\CampaignQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

describe('Campaign Model', function () {
    uses(TestCase::class, RefreshDatabase::class);
    test('has correct status constants', function () {
        expect(Campaign::STATUS_DRAFT)->toBe('draft')
            ->and(Campaign::STATUS_SCHEDULED)->toBe('scheduled')
            ->and(Campaign::STATUS_SENDING)->toBe('sending')
            ->and(Campaign::STATUS_SENT)->toBe('sent');
    });

    test('generates unique shortcode on creation', function () {
        $list = NewsletterList::factory()->create();
        $campaign1 = Campaign::factory()->create(['newsletter_list_id' => $list->id]);
        $campaign2 = Campaign::factory()->create(['newsletter_list_id' => $list->id]);

        expect($campaign1->shortcode)->not->toBeNull()
            ->and($campaign2->shortcode)->not->toBeNull()
            ->and($campaign1->shortcode)->not->toBe($campaign2->shortcode)
            ->and(strlen($campaign1->shortcode))->toBe(8);
    });

    test('does not override existing shortcode', function () {
        $list = NewsletterList::factory()->create();
        $customShortcode = 'CUSTOM01';

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'shortcode' => $customShortcode
        ]);

        expect($campaign->shortcode)->toBe($customShortcode);
    });

    test('belongs to newsletter list', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create(['newsletter_list_id' => $list->id]);

        expect($campaign->newsletterList)->toBeInstanceOf(NewsletterList::class)
            ->and($campaign->newsletterList->id)->toBe($list->id);
    });

    test('calculates open rate correctly', function () {
        $list = NewsletterList::factory()->create();

        // Campaign with no sends
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 0,
            'opens' => 0
        ]);
        expect($campaign->open_rate)->toBe(0);

        // Campaign with sends and opens
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 100,
            'opens' => 25
        ]);
        expect($campaign->open_rate)->toBe(25.0);

        // Campaign with partial opens
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 300,
            'opens' => 100
        ]);
        expect($campaign->open_rate)->toBe(33.33);
    });

    test('calculates click rate correctly', function () {
        $list = NewsletterList::factory()->create();

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 200,
            'clicks' => 40
        ]);
        expect($campaign->click_rate)->toBe(20.0);

        // No sends
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 0,
            'clicks' => 5
        ]);
        expect($campaign->click_rate)->toBe(0);
    });

    test('calculates unsubscribe rate correctly', function () {
        $list = NewsletterList::factory()->create();

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 500,
            'unsubscribes' => 15
        ]);
        expect($campaign->unsubscribe_rate)->toBe(3.0);
    });

    test('calculates bounce rate correctly', function () {
        $list = NewsletterList::factory()->create();

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 1000,
            'bounces' => 50
        ]);
        expect($campaign->bounce_rate)->toBe(5.0);
    });

    test('status check methods work correctly', function () {
        $list = NewsletterList::factory()->create();

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT
        ]);
        expect($draftCampaign->isDraft())->toBeTrue();
        expect($draftCampaign->isScheduled())->toBeFalse();
        expect($draftCampaign->isSending())->toBeFalse();
        expect($draftCampaign->isSent())->toBeFalse();

        $scheduledCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED
        ]);
        expect($scheduledCampaign->isScheduled())->toBeTrue()
            ->and($scheduledCampaign->isDraft())->toBeFalse();

        $sendingCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);
        expect($sendingCampaign->isSending())->toBeTrue();

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT
        ]);
        expect($sentCampaign->isSent())->toBeTrue();
    });

    test('can edit method works correctly', function () {
        $list = NewsletterList::factory()->create();

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT
        ]);
        expect($draftCampaign->canEdit())->toBeTrue();

        $scheduledCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED
        ]);
        expect($scheduledCampaign->canEdit())->toBeTrue();

        $sendingCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);
        expect($sendingCampaign->canEdit())->toBeTrue();

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT
        ]);
        expect($sentCampaign->canEdit())->toBeFalse();
    });

    test('can delete method works correctly', function () {
        $list = NewsletterList::factory()->create();

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT
        ]);
        expect($draftCampaign->canDelete())->toBeTrue();

        $scheduledCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED
        ]);
        expect($scheduledCampaign->canDelete())->toBeFalse();

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT
        ]);
        expect($sentCampaign->canDelete())->toBeFalse();
    });

    test('can send method works correctly with subscribers', function () {
        $list = NewsletterList::factory()->create();

        // Create subscribed subscribers
        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT
        ]);
        expect($draftCampaign->canSend())->toBeTrue();

        $scheduledCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED
        ]);
        expect($scheduledCampaign->canSend())->toBeTrue();

        $sendingCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);
        expect($sendingCampaign->canSend())->toBeFalse();

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT
        ]);
        expect($sentCampaign->canSend())->toBeFalse();
    });

    test('can send method returns false with no subscribers', function () {
        $list = NewsletterList::factory()->create();

        // No subscribers in the list
        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT
        ]);
        expect($draftCampaign->canSend())->toBeFalse();
    });

    test('can send method returns false with only unsubscribed subscribers', function () {
        $list = NewsletterList::factory()->create();

        // Create only unsubscribed subscribers
        NewsletterSubscriber::factory()->count(2)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed'
        ]);

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT
        ]);
        expect($draftCampaign->canSend())->toBeFalse();
    });

    test('sent at friendly attribute works correctly', function () {
        $list = NewsletterList::factory()->create();

        // Campaign without sent_at
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_at' => null
        ]);
        expect($campaign->sent_at_friendly)->toBe('N/A');

        // Campaign with sent_at
        $sentTime = now()->subHours(2);
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_at' => $sentTime
        ]);
        expect($campaign->sent_at_friendly)->toContain('hours ago');
    });

    test('scheduled at friendly attribute works correctly', function () {
        $list = NewsletterList::factory()->create();

        // Campaign without scheduled_at
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'scheduled_at' => null
        ]);
        expect($campaign->scheduled_at_friendly)->toBe('N/A');

        // Campaign with scheduled_at
        $scheduledTime = now()->addHours(1);
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'scheduled_at' => $scheduledTime
        ]);
        expect($campaign->scheduled_at_friendly)->toContain('from now');
    });

    test('casts work correctly', function () {
        $list = NewsletterList::factory()->create();
        $blocks = [
            ['type' => 'text', 'content' => 'Hello world'],
            ['type' => 'image', 'src' => 'image.jpg']
        ];

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'blocks' => $blocks,
            'sent_at' => '2024-01-01 12:00:00',
            'scheduled_at' => '2024-02-01 15:30:00'
        ]);

        expect($campaign->blocks)->toBeArray()
            ->and($campaign->blocks)->toBe($blocks)
            ->and($campaign->sent_at)->toBeInstanceOf(Carbon::class)
            ->and($campaign->scheduled_at)->toBeInstanceOf(Carbon::class);
    });

    test('uses custom query builder', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create(['newsletter_list_id' => $list->id]);

        $query = Campaign::query();
        expect($query)->toBeInstanceOf(CampaignQueryBuilder::class);
    });

    test('shortcode generation handles collisions', function () {
        $list = NewsletterList::factory()->create();

        // Create campaign with specific shortcode to test collision avoidance
        $existingCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'shortcode' => 'TESTCODE'
        ]);

        // Create new campaign - should get different shortcode
        $newCampaign = Campaign::factory()->create(['newsletter_list_id' => $list->id]);

        expect($newCampaign->shortcode)->not->toBe('TESTCODE')
            ->and($newCampaign->shortcode)->not->toBe($existingCampaign->shortcode);
    });

    test('rate calculations handle edge cases', function () {
        $list = NewsletterList::factory()->create();

        // Test with zero values
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 0,
            'opens' => 0,
            'clicks' => 0,
            'unsubscribes' => 0,
            'bounces' => 0
        ]);

        expect($campaign->open_rate)->toBe(0)
            ->and($campaign->click_rate)->toBe(0)
            ->and($campaign->unsubscribe_rate)->toBe(0)
            ->and($campaign->bounce_rate)->toBe(0);

        // Test with very small numbers
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'sent_count' => 3,
            'opens' => 1,
            'clicks' => 1,
            'unsubscribes' => 1,
            'bounces' => 1
        ]);

        expect($campaign->open_rate)->toBe(33.33)
            ->and($campaign->click_rate)->toBe(33.33)
            ->and($campaign->unsubscribe_rate)->toBe(33.33)
            ->and($campaign->bounce_rate)->toBe(33.33);
    });
});
