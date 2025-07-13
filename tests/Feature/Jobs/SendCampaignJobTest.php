<?php

use App\Jobs\SendCampaignJob;
use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Mail\CampaignEmail;
use App\Events\CampaignStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

describe('SendCampaignJob', function () {
    beforeEach(function () {
        Mail::fake();
        Event::fake();
        Config::set('newsletters.campaign_send_delay', 0); // Disable artificial delay in tests
    });

    test('sends campaign emails to all subscribed subscribers', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        // Create subscribers
        $subscriber1 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);
        $subscriber2 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);
        $subscriber3 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed' // Should not receive email
        ]);

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $campaign->refresh();

        // Check campaign status was updated
        expect($campaign->status)->toBe(Campaign::STATUS_SENT)
            ->and($campaign->sent_count)->toBe(2)
            ->and($campaign->bounces)->toBe(0)
            ->and($campaign->sent_at)->not->toBeNull();

        // Check emails were sent to subscribed users only
        Mail::assertSent(CampaignEmail::class, 2);
        Mail::assertSent(CampaignEmail::class, function ($mail) use ($subscriber1, $campaign) {
            return $mail->hasTo($subscriber1->email) && $mail->campaign->id === $campaign->id;
        });
        Mail::assertSent(CampaignEmail::class, function ($mail) use ($subscriber2, $campaign) {
            return $mail->hasTo($subscriber2->email) && $mail->campaign->id === $campaign->id;
        });

        // Check event was dispatched
        Event::assertDispatched(CampaignStatusChanged::class, function ($event) use ($campaign) {
            return $event->campaign->id === $campaign->id &&
                   $event->newStatus === Campaign::STATUS_SENT;
        });
    });

    test('handles no subscribers gracefully', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $campaign->refresh();

        expect($campaign->status)->toBe(Campaign::STATUS_SENT)
            ->and($campaign->sent_count)->toBe(0)
            ->and($campaign->bounces)->toBe(0)
            ->and($campaign->sent_at)->not->toBeNull();

        Mail::assertNothingSent();
        Event::assertDispatched(CampaignStatusChanged::class);
    });

    test('tracks bounces when email sending fails', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        $subscriber1 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'email' => 'valid@example.com'
        ]);
        $subscriber2 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'email' => 'bounce@example.com'
        ]);

        // Mock Mail to throw exception for specific email
        Mail::shouldReceive('to')->with('valid@example.com')->andReturnSelf();
        Mail::shouldReceive('send')->with(\Mockery::type(CampaignEmail::class))->once();

        Mail::shouldReceive('to')->with('bounce@example.com')->andReturnSelf();
        Mail::shouldReceive('send')->with(\Mockery::type(CampaignEmail::class))->andThrow(new \Exception('Email bounce'));

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $campaign->refresh();

        expect($campaign->status)->toBe(Campaign::STATUS_SENT)
            ->and($campaign->sent_count)->toBe(1)
            ->and($campaign->bounces)->toBe(1);
    });

    test('only sends to subscribed subscribers', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        // Create subscribers with different statuses
        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);
        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed'
        ]);

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $campaign->refresh();

        expect($campaign->sent_count)->toBe(1);
        Mail::assertSent(CampaignEmail::class, 1);
    });

    test('updates campaign status from previous status', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);

        $job = new SendCampaignJob($campaign);
        $job->handle();

        Event::assertDispatched(CampaignStatusChanged::class, function ($event) use ($campaign) {
            return $event->campaign->id === $campaign->id &&
                   $event->previousStatus === Campaign::STATUS_SENDING &&
                   $event->newStatus === Campaign::STATUS_SENT;
        });
    });

    test('handles empty newsletter list', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        // No subscribers created

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $campaign->refresh();

        expect($campaign->status)->toBe(Campaign::STATUS_SENT)
            ->and($campaign->sent_count)->toBe(0)
            ->and($campaign->bounces)->toBe(0);

        Mail::assertNothingSent();
    });

    test('sends emails with correct campaign and subscriber data', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING,
            'subject' => 'Test Campaign Subject',
            'content' => 'Test campaign content'
        ]);

        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $job = new SendCampaignJob($campaign);
        $job->handle();

        Mail::assertSent(CampaignEmail::class, function ($mail) use ($campaign, $subscriber) {
            return $mail->hasTo('test@example.com') &&
                   $mail->campaign->id === $campaign->id &&
                   $mail->subscriber->id === $subscriber->id;
        });
    });

    test('sets sent_at timestamp when job completes', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING,
            'sent_at' => null
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);

        $beforeTime = now();

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $afterTime = now();
        $campaign->refresh();

        expect($campaign->sent_at)->not->toBeNull()
            ->and($campaign->sent_at->toDateTimeString())->toBeGreaterThanOrEqual($beforeTime->toDateTimeString())
            ->and($campaign->sent_at->toDateTimeString())->toBeLessThanOrEqual($afterTime->toDateTimeString());
    });

    test('handles large number of subscribers', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        // Create 50 subscribers
        for ($i = 0; $i < 50; $i++) {
            NewsletterSubscriber::factory()->create([
                'newsletter_list_id' => $list->id,
                'status' => 'subscribed',
                'email' => "user{$i}@example.com"
            ]);
        }

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $campaign->refresh();

        expect($campaign->status)->toBe(Campaign::STATUS_SENT)
            ->and($campaign->sent_count)->toBe(50)
            ->and($campaign->bounces)->toBe(0);

        Mail::assertSent(CampaignEmail::class, 50);
    });

    test('checks development delay configuration setting', function () {
        // Test that the job correctly checks the environment and delay config
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);

        // Set delay config
        Config::set('newsletters.campaign_send_delay', 5);

        $job = new SendCampaignJob($campaign);

        // Test that job runs without delay in test environment
        $startTime = microtime(true);
        $job->handle();
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should be fast in testing environment (less than 0.1 seconds)
        expect($duration)->toBeLessThan(0.1);

        $campaign->refresh();
        expect($campaign->status)->toBe(Campaign::STATUS_SENT);
    });

    test('handles mixed bounce and success scenarios', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => Campaign::STATUS_SENDING
        ]);

        $successSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'email' => 'success@example.com'
        ]);
        $bounceSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'email' => 'bounce@example.com'
        ]);

        // Mock specific email behaviors
        Mail::shouldReceive('to')->with('success@example.com')->andReturnSelf();
        Mail::shouldReceive('send')->with(\Mockery::type(CampaignEmail::class))->once();

        Mail::shouldReceive('to')->with('bounce@example.com')->andReturnSelf();
        Mail::shouldReceive('send')->with(\Mockery::type(CampaignEmail::class))->andThrow(new \Exception('Mail server error'));

        $job = new SendCampaignJob($campaign);
        $job->handle();

        $campaign->refresh();

        expect($campaign->sent_count)->toBe(1)
            ->and($campaign->bounces)->toBe(1)
            ->and($campaign->status)->toBe(Campaign::STATUS_SENT);
    });

    test('job has correct timeout setting', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id
        ]);

        $job = new SendCampaignJob($campaign);

        expect($job->timeout)->toBe(300);
    });
});
