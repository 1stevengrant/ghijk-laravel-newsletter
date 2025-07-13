<?php

use App\Models\User;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->list = NewsletterList::factory()->create();
});

describe('toggle subscriber status', function () {
    test('toggles subscribed subscriber to unsubscribed', function () {
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed',
            'subscribed_at' => now()->subDay(),
            'unsubscribed_at' => null,
        ]);

        $response = $this->post(route('subscribers.toggle-status', $subscriber));

        $response->assertRedirect()
            ->assertSessionHas('success', 'Subscriber status updated.');

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed')
            ->and($subscriber->unsubscribed_at)->not->toBeNull();
    });

    test('toggles unsubscribed subscriber to subscribed', function () {
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'unsubscribed',
            'subscribed_at' => null,
            'unsubscribed_at' => now()->subDay(),
        ]);

        $response = $this->post(route('subscribers.toggle-status', $subscriber));

        $response->assertRedirect()
            ->assertSessionHas('success', 'Subscriber status updated.');

        $subscriber->refresh();
        expect($subscriber->status)->toBe('subscribed')
            ->and($subscriber->subscribed_at)->not->toBeNull();
    });

    test('updates timestamp when toggling to subscribed', function () {
        $oldTimestamp = now()->subWeek();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'unsubscribed',
            'subscribed_at' => $oldTimestamp,
        ]);

        $response = $this->post(route('subscribers.toggle-status', $subscriber));

        $response->assertRedirect();

        $subscriber->refresh();
        expect($subscriber->subscribed_at)->not->toEqual($oldTimestamp)
            ->and($subscriber->subscribed_at)->toBeGreaterThan($oldTimestamp);
    });

    test('updates timestamp when toggling to unsubscribed', function () {
        $oldTimestamp = now()->subWeek();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed',
            'unsubscribed_at' => $oldTimestamp,
        ]);

        $response = $this->post(route('subscribers.toggle-status', $subscriber));

        $response->assertRedirect();

        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->not->toEqual($oldTimestamp)
            ->and($subscriber->unsubscribed_at)->toBeGreaterThan($oldTimestamp);
    });

    test('returns 404 for non-existent subscriber', function () {
        $response = $this->post(route('subscribers.toggle-status', 999));

        $response->assertNotFound();
    });

    test('redirects guests to login', function () {
        auth()->logout();

        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id
        ]);

        $response = $this->post(route('subscribers.toggle-status', $subscriber));

        $response->assertRedirect(route('login'));
    });

    test('works with unsubscribed status', function () {
        // Test with unsubscribed status
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'unsubscribed',
        ]);

        $response = $this->post(route('subscribers.toggle-status', $subscriber));

        $response->assertRedirect();

        $subscriber->refresh();
        // Should toggle to 'subscribed' since it wasn't 'subscribed'
        expect($subscriber->status)->toBe('subscribed')
            ->and($subscriber->subscribed_at)->not->toBeNull();
    });
});
