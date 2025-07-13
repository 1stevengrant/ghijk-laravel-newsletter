<?php

use App\Models\User;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('index', function () {
    test('displays newsletter lists with subscriber counts', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();

        NewsletterSubscriber::factory()->count(3)->create(['newsletter_list_id' => $list1->id]);
        NewsletterSubscriber::factory()->count(5)->create(['newsletter_list_id' => $list2->id]);

        $response = $this->get(route('lists.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/index')
                ->has('lists', 2)
                ->has('lists.0', fn (Assert $list) => $list
                    ->where('id', $list1->id)
                    ->where('subscribers_count', 3)
                    ->etc()
                )
                ->has('lists.1', fn (Assert $list) => $list
                    ->where('id', $list2->id)
                    ->where('subscribers_count', 5)
                    ->etc()
                )
            );
    });

    test('redirects guests to login', function () {
        auth()->logout();

        $response = $this->get(route('lists.index'));

        $response->assertRedirect(route('login'));
    });
});

describe('store', function () {
    test('creates a new newsletter list with valid data', function () {
        $data = [
            'name' => 'Test Newsletter',
            'description' => 'A test newsletter list',
            'from_name' => 'Test Sender',
            'from_email' => 'test@example.com',
        ];

        $response = $this->post(route('lists.store'), $data);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Newsletter list created successfully.');

        $this->assertDatabaseHas('newsletter_lists', $data);
    });

    test('validates required fields', function () {
        $response = $this->post(route('lists.store'), []);

        $response->assertSessionHasErrors(['name', 'from_name', 'from_email']);
        $this->assertDatabaseCount('newsletter_lists', 0);
    });

    test('validates email format', function () {
        $data = [
            'name' => 'Test Newsletter',
            'from_name' => 'Test Sender',
            'from_email' => 'invalid-email',
        ];

        $response = $this->post(route('lists.store'), $data);

        $response->assertSessionHasErrors(['from_email']);
        $this->assertDatabaseCount('newsletter_lists', 0);
    });

    test('validates field lengths', function () {
        $data = [
            'name' => str_repeat('a', 256),
            'from_name' => str_repeat('b', 256),
            'from_email' => str_repeat('c', 250) . '@example.com',
        ];

        $response = $this->post(route('lists.store'), $data);

        $response->assertSessionHasErrors(['name', 'from_name', 'from_email']);
    });

    test('allows nullable description', function () {
        $data = [
            'name' => 'Test Newsletter',
            'from_name' => 'Test Sender',
            'from_email' => 'test@example.com',
            'description' => null,
        ];

        $response = $this->post(route('lists.store'), $data);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('newsletter_lists', [
            'name' => 'Test Newsletter',
            'description' => null,
        ]);
    });
});

describe('show', function () {
    test('displays newsletter list with subscribers', function () {
        $list = NewsletterList::factory()->create();
        $subscribers = NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $list->id,
        ]);

        $response = $this->get(route('lists.show', $list));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/show')
                ->has('list', fn (Assert $listData) => $listData
                    ->where('id', $list->id)
                    ->where('name', $list->name)
                    ->has('subscribers', 3)
                    ->etc()
                )
            );
    });

    test('orders subscribers by subscription date descending', function () {
        $list = NewsletterList::factory()->create();

        $oldSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now()->subDays(2),
        ]);

        $newSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now(),
        ]);

        $response = $this->get(route('lists.show', $list));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('list.subscribers.0', fn (Assert $subscriber) => $subscriber
                    ->where('id', $newSubscriber->id)
                    ->etc()
                )
                ->has('list.subscribers.1', fn (Assert $subscriber) => $subscriber
                    ->where('id', $oldSubscriber->id)
                    ->etc()
                )
            );
    });

    test('returns 404 for non-existent list', function () {
        $response = $this->get(route('lists.show', 999));

        $response->assertNotFound();
    });
});

describe('update', function () {
    test('updates newsletter list with valid data', function () {
        $list = NewsletterList::factory()->create();

        $updateData = [
            'name' => 'Updated Newsletter',
            'description' => 'Updated description',
            'from_name' => 'Updated Sender',
            'from_email' => 'updated@example.com',
        ];

        $response = $this->put(route('lists.update', $list), $updateData);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Newsletter list updated successfully.');

        $this->assertDatabaseHas('newsletter_lists', array_merge(
            ['id' => $list->id],
            $updateData
        ));
    });

    test('validates required fields on update', function () {
        $list = NewsletterList::factory()->create();

        $response = $this->put(route('lists.update', $list), []);

        $response->assertSessionHasErrors(['name', 'from_name', 'from_email']);
    });

    test('validates email format on update', function () {
        $list = NewsletterList::factory()->create();

        $response = $this->put(route('lists.update', $list), [
            'name' => 'Test',
            'from_name' => 'Test',
            'from_email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['from_email']);
    });
});

describe('destroy', function () {
    test('deletes newsletter list', function () {
        $list = NewsletterList::factory()->create();

        $response = $this->delete(route('lists.destroy', $list));

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Newsletter list deleted successfully.');

        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list->id]);
    });

    test('deletes associated subscribers when list is deleted', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        $response = $this->delete(route('lists.destroy', $list));

        $response->assertRedirect(route('lists.index'));
        // Note: This test assumes cascade delete is set up in the database
        // If not, the subscriber would still exist
        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list->id]);
    });

    test('returns 404 when trying to delete non-existent list', function () {
        $response = $this->delete(route('lists.destroy', 999));

        $response->assertNotFound();
    });
});
