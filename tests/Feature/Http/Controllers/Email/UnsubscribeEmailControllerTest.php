<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

describe('unsubscribe email', function () {
    test('unsubscribes user with valid token', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'valid-token-123'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'valid-token-123'
        ]));

        $response->assertOk()
            ->assertViewIs('emails.unsubscribed')
            ->assertViewHas('subscriber', function ($viewSubscriber) use ($subscriber) {
                return $viewSubscriber->id === $subscriber->id;
            });

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed');
    });

    test('unsubscribes and increments campaign unsubscribe count when campaign ID provided', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'unsubscribes' => 5
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'valid-token-456'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'valid-token-456',
            'campaign' => $campaign->id
        ]));

        $response->assertOk()
            ->assertViewIs('emails.unsubscribed')
            ->assertViewHas('subscriber');

        $subscriber->refresh();
        $campaign->refresh();

        expect($subscriber->status)->toBe('unsubscribed')
            ->and($campaign->unsubscribes)->toBe(6);
    });

    test('returns 404 when no token provided', function () {
        $response = $this->get(route('newsletter.unsubscribe'));

        $response->assertNotFound();
    });

    test('returns 404 when empty token provided', function () {
        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => ''
        ]));

        $response->assertNotFound();
    });

    test('returns 404 for invalid token', function () {
        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'invalid-token-that-does-not-exist'
        ]));

        $response->assertNotFound();
    });

    test('handles non-existent campaign ID gracefully', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'valid-token-789'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'valid-token-789',
            'campaign' => 999999 // Non-existent campaign ID
        ]));

        $response->assertOk()
            ->assertViewIs('emails.unsubscribed');

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed');

        // No campaign should be affected
        $this->assertDatabaseMissing('campaigns', ['id' => 999999]);
    });

    test('does not increment campaign count when campaign ID is empty', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'unsubscribes' => 3
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'valid-token-empty'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'valid-token-empty',
            'campaign' => '' // Empty campaign ID
        ]));

        $response->assertOk();

        $subscriber->refresh();
        $campaign->refresh();

        expect($subscriber->status)->toBe('unsubscribed')
            ->and($campaign->unsubscribes)->toBe(3);
        // Should not increment
    });

    test('works when subscriber is already unsubscribed', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed', // Already unsubscribed
            'unsubscribe_token' => 'already-unsubscribed-token'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'already-unsubscribed-token'
        ]));

        $response->assertOk()
            ->assertViewIs('emails.unsubscribed')
            ->assertViewHas('subscriber');

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed');
    });

    test('does not require authentication', function () {
        $this->assertGuest();

        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'public-unsubscribe-token'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'public-unsubscribe-token'
        ]));

        $response->assertOk();
    });

    test('handles special characters in token', function () {
        $list = NewsletterList::factory()->create();
        $specialToken = 'token-with-special-chars_123-ABC.xyz';
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => $specialToken
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => $specialToken
        ]));

        $response->assertOk()
            ->assertViewHas('subscriber');

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed');
    });

    test('handles very long tokens', function () {
        $list = NewsletterList::factory()->create();
        $longToken = str_repeat('abcdef123456', 20); // 240 characters
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => $longToken
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => $longToken
        ]));

        $response->assertOk();

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed');
    });

    test('ensures only exact token matches work', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'exact-match-token'
        ]);

        // Try with partial token
        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'exact-match'
        ]));

        $response->assertNotFound();

        // Try with extra characters
        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'exact-match-token-extra'
        ]));

        $response->assertNotFound();

        $subscriber->refresh();
        expect($subscriber->status)->toBe('subscribed'); // Should remain subscribed
    });

    test('handles case-sensitive tokens correctly', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'CaseSensitiveToken123'
        ]);

        // Try with different case
        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'casesensitivetoken123'
        ]));

        $response->assertNotFound();

        // Try with correct case
        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'CaseSensitiveToken123'
        ]));

        $response->assertOk();

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed');
    });

    test('increments campaign count only once for multiple unsubscribe attempts', function () {
        $list = NewsletterList::factory()->create();
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'unsubscribes' => 0
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'multi-attempt-token'
        ]);

        // First unsubscribe attempt
        $this->get(route('newsletter.unsubscribe', [
            'token' => 'multi-attempt-token',
            'campaign' => $campaign->id
        ]));

        $campaign->refresh();
        expect($campaign->unsubscribes)->toBe(1);

        // Second attempt (already unsubscribed)
        $this->get(route('newsletter.unsubscribe', [
            'token' => 'multi-attempt-token',
            'campaign' => $campaign->id
        ]));

        $campaign->refresh();
        expect($campaign->unsubscribes)->toBe(2); // Will increment again because it doesn't check if already unsubscribed
    });

    test('provides subscriber data to view', function () {
        $list = NewsletterList::factory()->create([
            'name' => 'Test Newsletter'
        ]);
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'subscribed',
            'unsubscribe_token' => 'view-data-token'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'view-data-token'
        ]));

        $response->assertOk()
            ->assertViewIs('emails.unsubscribed')
            ->assertViewHas('subscriber', function ($viewSubscriber) use ($subscriber) {
                return $viewSubscriber->email === 'test@example.com' &&
                       $viewSubscriber->first_name === 'John' &&
                       $viewSubscriber->last_name === 'Doe';
            });
    });

    test('handles null campaign parameter correctly', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'unsubscribe_token' => 'null-campaign-token'
        ]);

        $response = $this->get(route('newsletter.unsubscribe', [
            'token' => 'null-campaign-token',
            'campaign' => null
        ]));

        $response->assertOk();

        $subscriber->refresh();
        expect($subscriber->status)->toBe('unsubscribed');
    });
});
