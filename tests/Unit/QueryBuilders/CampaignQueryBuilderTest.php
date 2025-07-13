<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use App\QueryBuilders\CampaignQueryBuilder;

describe('CampaignQueryBuilder', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    test('whereDraft filters campaigns by draft status', function () {
        $list = NewsletterList::factory()->create();

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT,
        ]);

        $results = Campaign::query()->whereDraft()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($draftCampaign->id)
            ->and($results->first()->status)->toBe(Campaign::STATUS_DRAFT);
    });

    test('whereScheduled filters campaigns by scheduled status', function () {
        $list = NewsletterList::factory()->create();

        $scheduledCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
        ]);

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $results = Campaign::query()->whereScheduled()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($scheduledCampaign->id)
            ->and($results->first()->status)->toBe(Campaign::STATUS_SCHEDULED);
    });

    test('whereSending filters campaigns by sending status', function () {
        $list = NewsletterList::factory()->create();

        $sendingCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING,
        ]);

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $results = Campaign::query()->whereSending()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($sendingCampaign->id)
            ->and($results->first()->status)->toBe(Campaign::STATUS_SENDING);
    });

    test('whereSent filters campaigns by sent status', function () {
        $list = NewsletterList::factory()->create();

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT,
        ]);

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $results = Campaign::query()->whereSent()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($sentCampaign->id)
            ->and($results->first()->status)->toBe(Campaign::STATUS_SENT);
    });

    test('whereStatus filters campaigns by any status', function () {
        $list = NewsletterList::factory()->create();

        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT,
        ]);

        $draftResults = Campaign::query()->whereStatus(Campaign::STATUS_DRAFT)->get();
        $sentResults = Campaign::query()->whereStatus(Campaign::STATUS_SENT)->get();

        expect($draftResults)->toHaveCount(1)
            ->and($draftResults->first()->id)->toBe($draftCampaign->id)
            ->and($sentResults)->toHaveCount(1)
            ->and($sentResults->first()->id)->toBe($sentCampaign->id);
    });

    test('countByStatus returns correct counts for all statuses', function () {
        $list = NewsletterList::factory()->create();

        // Create campaigns with different statuses
        Campaign::factory()->count(2)->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        Campaign::factory()->count(3)->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
        ]);

        Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING,
        ]);

        Campaign::factory()->count(4)->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT,
        ]);

        $counts = Campaign::query()->countByStatus();

        expect($counts)->toBeArray()
            ->and($counts['draft'])->toBe(2)
            ->and($counts['scheduled'])->toBe(3)
            ->and($counts['sending'])->toBe(1)
            ->and($counts['sent'])->toBe(4);
    });

    test('whereScheduledBefore filters campaigns scheduled before given date', function () {
        $list = NewsletterList::factory()->create();
        $pastDate = now()->subDays(2);
        $futureDate = now()->addDays(2);

        $pastCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => $pastDate,
        ]);

        $futureCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => $futureDate,
        ]);

        $results = Campaign::query()->whereScheduledBefore(now())->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($pastCampaign->id);
    });

    test('whereScheduledAfter filters campaigns scheduled after given date', function () {
        $list = NewsletterList::factory()->create();
        $pastDate = now()->subDays(2);
        $futureDate = now()->addDays(2);

        $pastCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => $pastDate,
        ]);

        $futureCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => $futureDate,
        ]);

        $results = Campaign::query()->whereScheduledAfter(now())->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($futureCampaign->id);
    });

    test('readyToSend filters campaigns ready to be sent', function () {
        $list = NewsletterList::factory()->create();

        // Campaign scheduled in the past - ready to send
        $readyCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->subHour(),
        ]);

        // Campaign scheduled in the future - not ready
        $futureCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);

        // Draft campaign - not ready
        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
            'scheduled_at' => now()->subHour(),
        ]);

        // Scheduled campaign with null scheduled_at - not ready
        $nullScheduledCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => null,
        ]);

        $results = Campaign::query()->readyToSend()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($readyCampaign->id);
    });

    test('orderByLatest orders campaigns by creation date descending', function () {
        $list = NewsletterList::factory()->create();

        $oldCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now()->subDays(2),
        ]);

        $newCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now(),
        ]);

        $results = Campaign::query()->orderByLatest()->get();

        expect($results->first()->id)->toBe($newCampaign->id)
            ->and($results->last()->id)->toBe($oldCampaign->id);
    });

    test('orderByOldest orders campaigns by creation date ascending', function () {
        $list = NewsletterList::factory()->create();

        $oldCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now()->subDays(2),
        ]);

        $newCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now(),
        ]);

        $results = Campaign::query()->orderByOldest()->get();

        expect($results->first()->id)->toBe($oldCampaign->id)
            ->and($results->last()->id)->toBe($newCampaign->id);
    });

    test('methods can be chained together', function () {
        $list = NewsletterList::factory()->create();

        $oldDraftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
            'created_at' => now()->subDays(2),
        ]);

        $newDraftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_DRAFT,
            'created_at' => now(),
        ]);

        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENT,
            'created_at' => now()->subDay(),
        ]);

        $results = Campaign::query()
            ->whereDraft()
            ->orderByLatest()
            ->get();

        expect($results)->toHaveCount(2)
            ->and($results->first()->id)->toBe($newDraftCampaign->id)
            ->and($results->last()->id)->toBe($oldDraftCampaign->id);
    });

    test('returns CampaignQueryBuilder instance', function () {
        $query = Campaign::query();

        expect($query)->toBeInstanceOf(CampaignQueryBuilder::class)
            ->and($query->whereDraft())->toBeInstanceOf(CampaignQueryBuilder::class)
            ->and($query->whereScheduled())->toBeInstanceOf(CampaignQueryBuilder::class)
            ->and($query->whereSending())->toBeInstanceOf(CampaignQueryBuilder::class)
            ->and($query->whereSent())->toBeInstanceOf(CampaignQueryBuilder::class);
    });

    test('withSubscriberCounts includes newsletter list with subscriber counts', function () {
        $list = NewsletterList::factory()->create();

        // Create subscribers for the list
        $subscribedCount = 5;
        $unsubscribedCount = 3;

        \App\Models\NewsletterSubscriber::factory()->count($subscribedCount)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
        ]);

        \App\Models\NewsletterSubscriber::factory()->count($unsubscribedCount)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
        ]);

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $result = Campaign::query()
            ->withSubscriberCounts()
            ->where('id', $campaign->id)
            ->first();

        expect($result->newsletterList)->not->toBeNull()
            ->and($result->newsletterList->subscribers_count)->toBe($subscribedCount);
    });

    test('handles empty result sets correctly', function () {
        $results = Campaign::query()->whereDraft()->get();
        $counts = Campaign::query()->countByStatus();

        expect($results)->toHaveCount(0)
            ->and($counts['draft'])->toBe(0)
            ->and($counts['scheduled'])->toBe(0)
            ->and($counts['sending'])->toBe(0)
            ->and($counts['sent'])->toBe(0);
    });

    test('handles date filtering edge cases', function () {
        $list = NewsletterList::factory()->create();

        // Campaign scheduled exactly now
        $nowCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now(),
        ]);

        $beforeResults = Campaign::query()->whereScheduledBefore(now())->get();
        $afterResults = Campaign::query()->whereScheduledAfter(now())->get();
        $readyResults = Campaign::query()->readyToSend()->get();

        // Campaign scheduled at exactly "now" should be included in "before or equal" queries
        expect($beforeResults)->toHaveCount(1)
            ->and($afterResults)->toHaveCount(1)
            ->and($readyResults)->toHaveCount(1);
    });
});
