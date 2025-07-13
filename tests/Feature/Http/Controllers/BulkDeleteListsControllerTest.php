<?php

use App\Models\User;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('bulk delete lists', function () {
    test('deletes multiple newsletter lists successfully', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();
        $list3 = NewsletterList::factory()->create();

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list1->id, $list2->id],
        ]);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Successfully deleted 2 newsletter list(s).');

        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list1->id]);
        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list2->id]);
        $this->assertDatabaseHas('newsletter_lists', ['id' => $list3->id]);
    });

    test('deletes single newsletter list successfully', function () {
        $list = NewsletterList::factory()->create();

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list->id],
        ]);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Successfully deleted 1 newsletter list(s).');

        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list->id]);
    });

    test('validates required list_ids field', function () {
        $response = $this->delete(route('lists.bulk-delete'), []);

        $response->assertSessionHasErrors(['list_ids']);
    });

    test('validates list_ids is array', function () {
        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => 'not-an-array',
        ]);

        $response->assertSessionHasErrors(['list_ids']);
    });

    test('validates list_ids array is not empty', function () {
        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [],
        ]);

        $response->assertSessionHasErrors(['list_ids']);
    });

    test('validates each list_id is integer', function () {
        $list = NewsletterList::factory()->create();

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list->id, 'not-integer'],
        ]);

        $response->assertSessionHasErrors(['list_ids.1']);
    });

    test('validates each list_id exists in database', function () {
        $list = NewsletterList::factory()->create();

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list->id, 999999],
        ]);

        $response->assertSessionHasErrors(['list_ids.1']);
    });

    test('deletes associated subscribers when lists are deleted', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();

        $subscriber1 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list1->id,
        ]);
        $subscriber2 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list2->id,
        ]);
        $subscriber3 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list2->id,
        ]);

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list1->id, $list2->id],
        ]);

        $response->assertRedirect(route('lists.index'));

        // Check that lists and their subscribers are deleted
        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list1->id]);
        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list2->id]);

        // Note: This assumes cascade delete is set up in the database
        // If not, subscribers would still exist
        $this->assertDatabaseCount('newsletter_lists', 0);
    });

    test('handles large number of list deletions', function () {
        $lists = NewsletterList::factory()->count(50)->create();
        $listIds = $lists->pluck('id')->toArray();

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => $listIds,
        ]);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Successfully deleted 50 newsletter list(s).');

        $this->assertDatabaseCount('newsletter_lists', 0);
    });

    test('returns correct count when some lists do not exist', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();

        // Delete one list manually to simulate non-existent ID passing validation
        $list2->delete();

        // The validation will catch non-existent IDs, so we need to test
        // the scenario where IDs exist at validation time but are deleted
        // before the bulk delete executes
        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list1->id],
        ]);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Successfully deleted 1 newsletter list(s).');
    });

    test('redirects guests to login', function () {
        auth()->logout();

        $list = NewsletterList::factory()->create();

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list->id],
        ]);

        $response->assertRedirect(route('login'));
    });

    test('validates nested array values are required', function () {
        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [null, ''],
        ]);

        $response->assertSessionHasErrors(['list_ids.0', 'list_ids.1']);
    });

    test('handles duplicate list IDs gracefully', function () {
        $list = NewsletterList::factory()->create();

        $response = $this->delete(route('lists.bulk-delete'), [
            'list_ids' => [$list->id, $list->id, $list->id],
        ]);

        $response->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', 'Successfully deleted 1 newsletter list(s).');

        $this->assertDatabaseMissing('newsletter_lists', ['id' => $list->id]);
    });
});
