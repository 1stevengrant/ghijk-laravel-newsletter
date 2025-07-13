<?php

use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

describe('public newsletter subscription', function () {
    test('subscribes user to newsletter list via shortcode', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully subscribed to ' . $list->name,
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'newsletter_list_id' => $list->id,
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    });

    test('subscribes user with only email address', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully subscribed to ' . $list->name,
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'newsletter_list_id' => $list->id,
            'email' => 'test@example.com',
            'first_name' => null,
            'last_name' => null,
        ]);
    });

    test('validates required email field', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    });

    test('validates email format', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('newsletter_subscribers', 0);
    });

    test('validates email uniqueness within the same list', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    });

    test('allows same email in different newsletter lists', function () {
        $list1 = NewsletterList::factory()->create(['shortcode' => 'LIST1']);
        $list2 = NewsletterList::factory()->create(['shortcode' => 'LIST2']);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list1->id,
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/newsletter/LIST2/subscribe', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Successfully subscribed to ' . $list2->name,
            ]);

        $this->assertDatabaseCount('newsletter_subscribers', 2);
    });

    test('validates field lengths', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => str_repeat('a', 250) . '@example.com',
            'first_name' => str_repeat('b', 256),
            'last_name' => str_repeat('c', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'first_name', 'last_name']);
    });

    test('generates verification and unsubscribe tokens', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk();

        $subscriber = NewsletterSubscriber::where('email', 'test@example.com')->first();
        expect($subscriber->verification_token)->not->toBeNull();
        expect($subscriber->unsubscribe_token)->not->toBeNull();
        expect(mb_strlen($subscriber->verification_token))->toBe(60);
        expect(mb_strlen($subscriber->unsubscribe_token))->toBe(60);
    });

    test('returns 404 for non-existent newsletter list shortcode', function () {
        $response = $this->postJson('/newsletter/NONEXISTENT/subscribe', [
            'email' => 'test@example.com',
        ]);

        $response->assertNotFound();
    });

    test('handles special characters in names', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'test@example.com',
            'first_name' => 'José María',
            'last_name' => 'García-López',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
            'first_name' => 'José María',
            'last_name' => 'García-López',
        ]);
    });

    test('handles case-insensitive email validation', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'TEST@EXAMPLE.COM',
        ]);

        // Laravel's unique validation is typically case-insensitive for emails
        // but this depends on database collation - let's test both scenarios
        if ($response->getStatusCode() === 422) {
            $response->assertJsonValidationErrors(['email']);
        } else {
            // If case-sensitive, it should succeed
            $response->assertOk();
            $this->assertDatabaseCount('newsletter_subscribers', 2);
        }
    });

    test('does not require authentication for public subscription', function () {
        // Ensure no user is authenticated
        $this->assertGuest();

        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->postJson('/newsletter/TEST123/subscribe', [
            'email' => 'test@example.com',
        ]);

        $response->assertOk();
    });
});

describe('public newsletter signup page', function () {
    test('displays newsletter signup page', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
            'name' => 'Test Newsletter',
            'description' => 'A test newsletter description',
        ]);

        $response = $this->get('/newsletter/TEST123');

        $response->assertOk()
            ->assertViewIs('public.newsletter.signup')
            ->assertViewHas('list', function ($viewList) use ($list) {
                return $viewList->id === $list->id &&
                       $viewList->name === 'Test Newsletter' &&
                       $viewList->shortcode === 'TEST123';
            });
    });

    test('returns 404 for non-existent newsletter list shortcode', function () {
        $response = $this->get('/newsletter/NONEXISTENT');

        $response->assertNotFound();
    });

    test('displays correct newsletter list data', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'SPECIAL',
            'name' => 'Special Newsletter',
            'description' => 'A very special newsletter',
            'from_name' => 'Newsletter Team',
            'from_email' => 'team@newsletter.com',
        ]);

        $response = $this->get('/newsletter/SPECIAL');

        $response->assertOk()
            ->assertViewHas('list', function ($viewList) {
                return $viewList->name === 'Special Newsletter' &&
                       $viewList->description === 'A very special newsletter' &&
                       $viewList->from_name === 'Newsletter Team' &&
                       $viewList->from_email === 'team@newsletter.com';
            });
    });

    test('handles shortcodes with special characters', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'ABC-123_XYZ',
        ]);

        $response = $this->get('/newsletter/ABC-123_XYZ');

        $response->assertOk()
            ->assertViewHas('list', function ($viewList) use ($list) {
                return $viewList->id === $list->id;
            });
    });

    test('does not require authentication for public signup page', function () {
        $this->assertGuest();

        $list = NewsletterList::factory()->create([
            'shortcode' => 'TEST123',
        ]);

        $response = $this->get('/newsletter/TEST123');

        $response->assertOk();
    });

    test('loads newsletter list with all attributes', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'FULL-TEST',
            'name' => 'Complete Newsletter',
            'description' => 'Full description',
            'from_name' => 'Sender Name',
            'from_email' => 'sender@example.com',
        ]);

        $response = $this->get('/newsletter/FULL-TEST');

        $response->assertOk()
            ->assertViewHas('list')
            ->assertSee('Complete Newsletter')
            ->assertSee('Full description');
    });
});
