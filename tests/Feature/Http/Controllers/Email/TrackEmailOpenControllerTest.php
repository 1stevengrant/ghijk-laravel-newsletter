<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;

describe('track email open', function () {
    test('tracks first email open and increments campaign opens count', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'opens' => 5
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $response = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/gif')
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');

        // Check that campaign opens count was incremented
        $campaign->refresh();
        expect($campaign->opens)->toBe(6);

        // Check that a record was created in campaign_opens table
        $this->assertDatabaseHas('campaign_opens', [
            'campaign_id' => $campaign->id,
            'newsletter_subscriber_id' => $subscriber->id,
        ]);
    });

    test('does not increment opens count for duplicate opens from same subscriber', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'opens' => 10
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        // First open
        $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $campaign->refresh();
        expect($campaign->opens)->toBe(11);

        // Second open from same subscriber
        $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $campaign->refresh();
        expect($campaign->opens)->toBe(11); // Should not increment again

        // Should still have only one record in campaign_opens
        $openRecords = DB::table('campaign_opens')
            ->where('campaign_id', $campaign->id)
            ->where('newsletter_subscriber_id', $subscriber->id)
            ->count();

        expect($openRecords)->toBe(1);
    });

    test('tracks opens from different subscribers separately', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'opens' => 0
        ]);
        $subscriber1 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);
        $subscriber2 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        // First subscriber opens
        $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber1
        ]));

        // Second subscriber opens
        $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber2
        ]));

        $campaign->refresh();
        expect($campaign->opens)->toBe(2);

        // Should have two separate records
        $openRecords = DB::table('campaign_opens')
            ->where('campaign_id', $campaign->id)
            ->count();

        expect($openRecords)->toBe(2);
    });

    test('records IP address and user agent', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Test Browser)',
        ])->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response->assertOk();

        $this->assertDatabaseHas('campaign_opens', [
            'campaign_id' => $campaign->id,
            'newsletter_subscriber_id' => $subscriber->id,
            'ip_address' => '127.0.0.1', // Default test IP
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
        ]);
    });

    test('records timestamp correctly', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $beforeTime = now();

        $response = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $afterTime = now();

        $response->assertOk();

        $openRecord = DB::table('campaign_opens')
            ->where('campaign_id', $campaign->id)
            ->where('newsletter_subscriber_id', $subscriber->id)
            ->first();

        expect($openRecord->opened_at)->toBeGreaterThanOrEqual($beforeTime->toDateTimeString())
            ->and($openRecord->opened_at)->toBeLessThanOrEqual($afterTime->toDateTimeString());
    });

    test('returns transparent pixel image', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $response = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response->assertOk();

        // Check that response contains the base64 decoded pixel data
        $expectedPixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        expect($response->getContent())->toBe($expectedPixel);

        // Check Content-Length header
        $response->assertHeader('Content-Length', (string) mb_strlen($expectedPixel));
    });

    test('sets proper cache headers to prevent caching', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $response = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response->assertOk()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');
    });

    test('returns 404 for non-existent campaign', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $response = $this->get(route('campaign.track.open', [
            'campaign' => 999,
            'subscriber' => $subscriber
        ]));

        $response->assertNotFound();
    });

    test('returns 404 for non-existent subscriber', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $response = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => 999
        ]));

        $response->assertNotFound();
    });

    test('does not require authentication', function () {
        $this->assertGuest();

        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'opens' => 0
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $response = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response->assertOk();
    });

    test('handles missing user agent gracefully', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        // Clear headers to simulate missing User-Agent
        $response = $this->withHeaders([])->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response->assertOk();

        $this->assertDatabaseHas('campaign_opens', [
            'campaign_id' => $campaign->id,
            'newsletter_subscriber_id' => $subscriber->id,
        ]);
    });

    test('handles concurrent requests from same subscriber correctly', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'opens' => 0
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        // Simulate concurrent requests (in real scenario these would be parallel)
        $response1 = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response2 = $this->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response1->assertOk();
        $response2->assertOk();

        $campaign->refresh();
        // Should still only be 1 open due to insertOrIgnore
        expect($campaign->opens)->toBe(1);

        $openRecords = DB::table('campaign_opens')
            ->where('campaign_id', $campaign->id)
            ->where('newsletter_subscriber_id', $subscriber->id)
            ->count();

        expect($openRecords)->toBe(1);
    });

    test('tracks opens for multiple campaigns from same subscriber', function () {
        $list = NewsletterList::factory()->create();
        $campaign1 = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'opens' => 0
        ]);
        $campaign2 = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'opens' => 0
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        // Open first campaign
        $this->get(route('campaign.track.open', [
            'campaign' => $campaign1,
            'subscriber' => $subscriber
        ]));

        // Open second campaign
        $this->get(route('campaign.track.open', [
            'campaign' => $campaign2,
            'subscriber' => $subscriber
        ]));

        $campaign1->refresh();
        $campaign2->refresh();

        expect($campaign1->opens)->toBe(1)
            ->and($campaign2->opens)->toBe(1);

        // Should have two separate tracking records
        $totalRecords = DB::table('campaign_opens')
            ->where('newsletter_subscriber_id', $subscriber->id)
            ->count();

        expect($totalRecords)->toBe(2);
    });

    test('handles special characters in user agent', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $specialUserAgent = 'Mozilla/5.0 (特殊字符) WebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

        $response = $this->withHeaders([
            'User-Agent' => $specialUserAgent,
        ])->get(route('campaign.track.open', [
            'campaign' => $campaign,
            'subscriber' => $subscriber
        ]));

        $response->assertOk();

        $this->assertDatabaseHas('campaign_opens', [
            'campaign_id' => $campaign->id,
            'newsletter_subscriber_id' => $subscriber->id,
            'user_agent' => $specialUserAgent,
        ]);
    });
});
