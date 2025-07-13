<?php

use App\Models\User;
use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Jobs\SendCampaignJob;
use App\Events\CampaignStatusChanged;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->list = NewsletterList::factory()->create();
    Queue::fake();
    Event::fake();
});

describe('send campaign', function () {
    test('sends campaign successfully when conditions are met', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'scheduled',
        ]);

        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed'
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success', 'Campaign is being sent.');

        $campaign->refresh();
        expect($campaign->status)->toBe(Campaign::STATUS_SENDING);

        Queue::assertPushed(SendCampaignJob::class, function ($job) use ($campaign) {
            return $job->campaign->id === $campaign->id;
        });

        Event::assertDispatched(CampaignStatusChanged::class, function ($event) use ($campaign) {
            return $event->campaign->id === $campaign->id &&
                   $event->previousStatus === 'scheduled' &&
                   $event->newStatus === Campaign::STATUS_SENDING;
        });
    });

    test('prevents sending campaign that cannot be sent', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft', // Draft campaigns typically cannot be sent
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Campaign cannot be sent in its current state.');

        $campaign->refresh();
        expect($campaign->status)->toBe('draft');

        Queue::assertNotPushed(SendCampaignJob::class);
        Event::assertNotDispatched(CampaignStatusChanged::class);
    });

    test('prevents sending campaign with no subscribed subscribers', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'scheduled',
        ]);

        // Create unsubscribed subscribers only
        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'unsubscribed'
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Campaign cannot be sent in its current state.');

        $campaign->refresh();
        expect($campaign->status)->toBe('scheduled');

        Queue::assertNotPushed(SendCampaignJob::class);
    });

    test('prevents sending campaign with empty newsletter list', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'scheduled',
        ]);

        // No subscribers created

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Campaign cannot be sent in its current state.');
    });

    test('only counts subscribed subscribers for validation', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'scheduled',
        ]);

        // Mix of subscribed and unsubscribed
        NewsletterSubscriber::factory()->count(2)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed'
        ]);
        NewsletterSubscriber::factory()->count(5)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'unsubscribed'
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success', 'Campaign is being sent.');

        Queue::assertPushed(SendCampaignJob::class);
    });

    test('updates campaign status immediately before dispatching job', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'scheduled',
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed'
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect();

        // Campaign status should be updated immediately
        $campaign->refresh();
        expect($campaign->status)->toBe(Campaign::STATUS_SENDING);
    });

    test('returns 404 for non-existent campaign', function () {
        $response = $this->post(route('campaigns.send', 999));

        $response->assertNotFound();
    });

    test('requires authentication', function () {
        auth()->logout();
        
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect(route('login'));
    });

    test('dispatches status change event with correct parameters', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'scheduled',
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed'
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        Event::assertDispatched(CampaignStatusChanged::class, function ($event) use ($campaign) {
            return $event->campaign->id === $campaign->id &&
                   $event->previousStatus === 'scheduled' &&
                   $event->newStatus === Campaign::STATUS_SENDING;
        });
    });

    test('handles campaigns that are already sending', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => Campaign::STATUS_SENDING,
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed'
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Campaign cannot be sent in its current state.');

        Queue::assertNotPushed(SendCampaignJob::class);
    });

    test('handles campaigns that are already sent', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'sent',
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed'
        ]);

        $response = $this->post(route('campaigns.send', $campaign));

        $response->assertRedirect()
            ->assertSessionHas('error', 'Campaign cannot be sent in its current state.');

        Queue::assertNotPushed(SendCampaignJob::class);
    });
});