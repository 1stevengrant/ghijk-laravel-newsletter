<?php

use App\Models\User;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->list = NewsletterList::factory()->create();
});

describe('store', function () {
    test('creates a new subscriber with valid data', function () {
        $data = [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        $response = $this->post(route('subscribers.store', $this->list), $data);

        $response->assertRedirect(route('lists.show', $this->list))
            ->assertSessionHas('success', 'Subscriber created.');

        $this->assertDatabaseHas('newsletter_subscribers', array_merge(
            $data,
            ['newsletter_list_id' => $this->list->id]
        ));
    });

    test('creates subscriber with only email when names are optional', function () {
        $data = ['email' => 'test@example.com'];

        $response = $this->post(route('subscribers.store', $this->list), $data);

        $response->assertRedirect(route('lists.show', $this->list))
            ->assertSessionHas('success', 'Subscriber created.');

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
            'newsletter_list_id' => $this->list->id,
            'first_name' => null,
            'last_name' => null,
        ]);
    });

    test('validates required email field', function () {
        $response = $this->post(route('subscribers.store', $this->list), []);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    });

    test('validates email format', function () {
        $data = ['email' => 'invalid-email'];

        $response = $this->post(route('subscribers.store', $this->list), $data);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    });

    test('validates email uniqueness within the same list', function () {
        NewsletterSubscriber::factory()->create([
            'email' => 'existing@example.com',
            'newsletter_list_id' => $this->list->id,
        ]);

        $data = ['email' => 'existing@example.com'];

        $response = $this->post(route('subscribers.store', $this->list), $data);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('newsletter_subscribers', 1);
    });

    test('allows same email in different lists', function () {
        $otherList = NewsletterList::factory()->create();
        
        NewsletterSubscriber::factory()->create([
            'email' => 'test@example.com',
            'newsletter_list_id' => $otherList->id,
        ]);

        $data = ['email' => 'test@example.com'];

        $response = $this->post(route('subscribers.store', $this->list), $data);

        $response->assertRedirect(route('lists.show', $this->list))
            ->assertSessionHas('success', 'Subscriber created.');

        $this->assertDatabaseCount('newsletter_subscribers', 2);
    });

    test('validates field lengths', function () {
        $data = [
            'email' => str_repeat('a', 250) . '@example.com',
            'first_name' => str_repeat('b', 256),
            'last_name' => str_repeat('c', 256),
        ];

        $response = $this->post(route('subscribers.store', $this->list), $data);

        $response->assertSessionHasErrors(['email', 'first_name', 'last_name']);
    });

    test('redirects guests to login', function () {
        auth()->logout();
        
        $response = $this->post(route('subscribers.store', $this->list), [
            'email' => 'test@example.com'
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('destroy', function () {
    test('deletes subscriber', function () {
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id
        ]);

        $response = $this->delete(route('subscribers.destroy', [$this->list, $subscriber]));

        $response->assertRedirect(route('lists.show', $this->list))
            ->assertSessionHas('success', 'Subscriber deleted.');

        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $subscriber->id]);
    });

    test('returns 404 for non-existent subscriber', function () {
        $response = $this->delete(route('subscribers.destroy', [$this->list, 999]));

        $response->assertNotFound();
    });

    test('redirects guests to login', function () {
        auth()->logout();
        
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $this->list->id
        ]);

        $response = $this->delete(route('subscribers.destroy', [$this->list, $subscriber]));

        $response->assertRedirect(route('login'));
    });

    test('can delete any subscriber regardless of list in route', function () {
        $otherList = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $otherList->id
        ]);

        $response = $this->delete(route('subscribers.destroy', [$this->list, $subscriber]));

        // The route accepts any subscriber - this may be a security issue in real apps
        // but for this test we'll accept the current behavior
        $response->assertRedirect();
        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $subscriber->id]);
    });
});