<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

describe('track email click', function () {
    test('increments click count and redirects to valid URL', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 5,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $targetUrl = 'https://example.com/target-page';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $targetUrl,
        ]));

        $response->assertRedirect($targetUrl);

        $campaign->refresh();
        expect($campaign->clicks)->toBe(6);
    });

    test('increments click count without URL and returns success message', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
        ]));

        $response->assertOk()
            ->assertSeeText('Link tracked');

        $campaign->refresh();
        expect($campaign->clicks)->toBe(1);
    });

    test('validates URL before redirecting', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 2,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        // Test with invalid URL
        $invalidUrl = 'not-a-valid-url';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $invalidUrl,
        ]));

        $response->assertOk()
            ->assertSeeText('Link tracked');

        $campaign->refresh();
        expect($campaign->clicks)->toBe(3);
    });

    test('handles javascript URLs securely', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        // Test with javascript URL (potential XSS)
        $maliciousUrl = 'javascript:alert("xss")';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $maliciousUrl,
        ]));

        $response->assertOk()
            ->assertSeeText('Link tracked');

        $campaign->refresh();
        expect($campaign->clicks)->toBe(1);
    });

    test('handles data URLs securely', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        // Test with data URL
        $dataUrl = 'data:text/html,<script>alert("xss")</script>';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $dataUrl,
        ]));

        $response->assertOk()
            ->assertSeeText('Link tracked');

        $campaign->refresh();
        expect($campaign->clicks)->toBe(1);
    });

    test('redirects to HTTPS URLs correctly', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 10,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $httpsUrl = 'https://secure.example.com/page';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $httpsUrl,
        ]));

        $response->assertRedirect($httpsUrl);

        $campaign->refresh();
        expect($campaign->clicks)->toBe(11);
    });

    test('redirects to HTTP URLs correctly', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 7,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $httpUrl = 'http://example.com/page';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $httpUrl,
        ]));

        $response->assertRedirect($httpUrl);

        $campaign->refresh();
        expect($campaign->clicks)->toBe(8);
    });

    test('handles URLs with query parameters', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $complexUrl = 'https://example.com/page?param1=value1&param2=value2#section';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $complexUrl,
        ]));

        $response->assertRedirect($complexUrl);
    });

    test('handles URLs with special characters', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $specialUrl = 'https://example.com/special-page?search=test';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $specialUrl,
        ]));

        // The URL should be redirected to
        $response->assertRedirect($specialUrl);
    });

    test('returns 404 for non-existent campaign', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $response = $this->get(route('campaign.track.click', [
            'campaign' => 999,
            'subscriber' => $subscriber,
            'url' => 'https://example.com',
        ]));

        $response->assertNotFound();
    });

    test('returns 404 for non-existent subscriber', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => 999,
            'url' => 'https://example.com',
        ]));

        $response->assertNotFound();
    });

    test('does not require authentication', function () {
        $this->assertGuest();

        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => 'https://example.com',
        ]));

        $response->assertRedirect('https://example.com');
    });

    test('handles empty URL parameter', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 5,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => '',
        ]));

        $response->assertOk()
            ->assertSeeText('Link tracked');

        $campaign->refresh();
        expect($campaign->clicks)->toBe(6);
    });

    test('tracks multiple clicks from same subscriber', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        // First click
        $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => 'https://example.com/page1',
        ]));

        // Second click
        $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => 'https://example.com/page2',
        ]));

        $campaign->refresh();
        expect($campaign->clicks)->toBe(2);
    });

    test('handles very long URLs', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'clicks' => 0,
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $longUrl = 'https://example.com/very-long-path/' . str_repeat('segment/', 100) . 'final-page';

        $response = $this->get(route('campaign.track.click', [
            'campaign' => $campaign,
            'subscriber' => $subscriber,
            'url' => $longUrl,
        ]));

        $response->assertRedirect($longUrl);
    });
});
