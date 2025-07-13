<?php

use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\QueryBuilders\NewsletterSubscriberQueryBuilder;

describe('NewsletterSubscriberQueryBuilder', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    test('whereSubscribed filters subscribers by subscribed status', function () {
        $list = NewsletterList::factory()->create();

        $subscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
        ]);

        $unsubscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
        ]);

        $results = NewsletterSubscriber::query()->whereSubscribed()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($subscribedUser->id)
            ->and($results->first()->status)->toBe('subscribed');
    });

    test('whereUnsubscribed filters subscribers by unsubscribed status', function () {
        $list = NewsletterList::factory()->create();

        $subscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
        ]);

        $unsubscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
        ]);

        $results = NewsletterSubscriber::query()->whereUnsubscribed()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($unsubscribedUser->id)
            ->and($results->first()->status)->toBe('unsubscribed');
    });

    test('whereStatus filters subscribers by any status', function () {
        $list = NewsletterList::factory()->create();

        $subscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
        ]);

        $unsubscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
        ]);

        $subscribedResults = NewsletterSubscriber::query()->whereStatus('subscribed')->get();
        $unsubscribedResults = NewsletterSubscriber::query()->whereStatus('unsubscribed')->get();

        expect($subscribedResults)->toHaveCount(1)
            ->and($subscribedResults->first()->id)->toBe($subscribedUser->id)
            ->and($unsubscribedResults)->toHaveCount(1)
            ->and($unsubscribedResults->first()->id)->toBe($unsubscribedUser->id);
    });

    test('whereVerified filters subscribers by verified status', function () {
        $list = NewsletterList::factory()->create();

        $verifiedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email_verified_at' => now(),
        ]);

        $unverifiedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email_verified_at' => null,
        ]);

        $results = NewsletterSubscriber::query()->whereVerified()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($verifiedUser->id)
            ->and($results->first()->email_verified_at)->not->toBeNull();
    });

    test('whereUnverified filters subscribers by unverified status', function () {
        $list = NewsletterList::factory()->create();

        $verifiedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email_verified_at' => now(),
        ]);

        $unverifiedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email_verified_at' => null,
        ]);

        $results = NewsletterSubscriber::query()->whereUnverified()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($unverifiedUser->id)
            ->and($results->first()->email_verified_at)->toBeNull();
    });

    test('whereEmail filters subscribers by email address', function () {
        $list = NewsletterList::factory()->create();
        $targetEmail = 'target@example.com';

        $targetUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email' => $targetEmail,
        ]);

        $otherUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email' => 'other@example.com',
        ]);

        $results = NewsletterSubscriber::query()->whereEmail($targetEmail)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($targetUser->id)
            ->and($results->first()->email)->toBe($targetEmail);
    });

    test('whereNewsletterList filters subscribers by newsletter list ID', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();

        $list1Subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list1->id,
        ]);

        $list2Subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list2->id,
        ]);

        $results = NewsletterSubscriber::query()->whereNewsletterList($list1->id)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($list1Subscriber->id)
            ->and($results->first()->newsletter_list_id)->toBe($list1->id);
    });

    test('countByStatus returns correct counts for all statuses', function () {
        $list = NewsletterList::factory()->create();

        // Create subscribers with different statuses
        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
        ]);

        NewsletterSubscriber::factory()->count(2)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
        ]);

        $counts = NewsletterSubscriber::query()->countByStatus();

        expect($counts)->toBeArray()
            ->and($counts['subscribed'])->toBe(3)
            ->and($counts['unsubscribed'])->toBe(2);
    });

    test('subscribedAfter filters subscribers by subscription date', function () {
        $list = NewsletterList::factory()->create();
        $cutoffDate = now()->subDays(5);

        $recentSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now()->subDays(2),
        ]);

        $oldSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now()->subDays(10),
        ]);

        $results = NewsletterSubscriber::query()->subscribedAfter($cutoffDate)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($recentSubscriber->id);
    });

    test('subscribedBefore filters subscribers by subscription date', function () {
        $list = NewsletterList::factory()->create();
        $cutoffDate = now()->subDays(5);

        $recentSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now()->subDays(2),
        ]);

        $oldSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now()->subDays(10),
        ]);

        $results = NewsletterSubscriber::query()->subscribedBefore($cutoffDate)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($oldSubscriber->id);
    });

    test('unsubscribedAfter filters subscribers by unsubscription date', function () {
        $list = NewsletterList::factory()->create();
        $cutoffDate = now()->subDays(5);

        $recentUnsubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
            'unsubscribed_at' => now()->subDays(2),
        ]);

        $oldUnsubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
            'unsubscribed_at' => now()->subDays(10),
        ]);

        $results = NewsletterSubscriber::query()->unsubscribedAfter($cutoffDate)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($recentUnsubscriber->id);
    });

    test('unsubscribedBefore filters subscribers by unsubscription date', function () {
        $list = NewsletterList::factory()->create();
        $cutoffDate = now()->subDays(5);

        $recentUnsubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
            'unsubscribed_at' => now()->subDays(2),
        ]);

        $oldUnsubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
            'unsubscribed_at' => now()->subDays(10),
        ]);

        $results = NewsletterSubscriber::query()->unsubscribedBefore($cutoffDate)->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($oldUnsubscriber->id);
    });

    test('orderByLatest orders subscribers by creation date descending', function () {
        $list = NewsletterList::factory()->create();

        $oldSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now()->subDays(2),
        ]);

        $newSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now(),
        ]);

        $results = NewsletterSubscriber::query()->orderByLatest()->get();

        expect($results->first()->id)->toBe($newSubscriber->id)
            ->and($results->last()->id)->toBe($oldSubscriber->id);
    });

    test('orderByOldest orders subscribers by creation date ascending', function () {
        $list = NewsletterList::factory()->create();

        $oldSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now()->subDays(2),
        ]);

        $newSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'created_at' => now(),
        ]);

        $results = NewsletterSubscriber::query()->orderByOldest()->get();

        expect($results->first()->id)->toBe($oldSubscriber->id)
            ->and($results->last()->id)->toBe($newSubscriber->id);
    });

    test('orderBySubscribedAt orders subscribers by subscription date descending', function () {
        $list = NewsletterList::factory()->create();

        $oldSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now()->subDays(2),
        ]);

        $newSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => now(),
        ]);

        $results = NewsletterSubscriber::query()->orderBySubscribedAt()->get();

        expect($results->first()->id)->toBe($newSubscriber->id)
            ->and($results->last()->id)->toBe($oldSubscriber->id);
    });

    test('searchByName searches first name, last name, and email', function () {
        $list = NewsletterList::factory()->create();

        $johnDoe = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $janeSmith = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
        ]);

        $bobJohnson = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob@company.com',
        ]);

        // Search by first name
        $johnResults = NewsletterSubscriber::query()->searchByName('John')->get();
        expect($johnResults)->toHaveCount(2); // John Doe and Bob Johnson

        // Search by last name
        $smithResults = NewsletterSubscriber::query()->searchByName('Smith')->get();
        expect($smithResults)->toHaveCount(1)
            ->and($smithResults->first()->id)->toBe($janeSmith->id);

        // Search by email domain
        $companyResults = NewsletterSubscriber::query()->searchByName('company')->get();
        expect($companyResults)->toHaveCount(1)
            ->and($companyResults->first()->id)->toBe($bobJohnson->id);

        // Case insensitive search
        $lowerResults = NewsletterSubscriber::query()->searchByName('jane')->get();
        expect($lowerResults)->toHaveCount(1)
            ->and($lowerResults->first()->id)->toBe($janeSmith->id);
    });

    test('methods can be chained together', function () {
        $list = NewsletterList::factory()->create();

        $subscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'first_name' => 'John',
            'created_at' => now()->subDay(),
        ]);

        $unsubscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
            'first_name' => 'John',
            'created_at' => now(),
        ]);

        $results = NewsletterSubscriber::query()
            ->whereSubscribed()
            ->searchByName('John')
            ->orderByLatest()
            ->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($subscribedUser->id)
            ->and($results->first()->status)->toBe('subscribed');
    });

    test('returns NewsletterSubscriberQueryBuilder instance', function () {
        $query = NewsletterSubscriber::query();

        expect($query)->toBeInstanceOf(NewsletterSubscriberQueryBuilder::class)
            ->and($query->whereSubscribed())->toBeInstanceOf(NewsletterSubscriberQueryBuilder::class)
            ->and($query->whereUnsubscribed())->toBeInstanceOf(NewsletterSubscriberQueryBuilder::class);
    });

    test('handles empty result sets correctly', function () {
        $results = NewsletterSubscriber::query()->whereSubscribed()->get();
        $counts = NewsletterSubscriber::query()->countByStatus();
        $searchResults = NewsletterSubscriber::query()->searchByName('nonexistent')->get();

        expect($results)->toHaveCount(0)
            ->and($searchResults)->toHaveCount(0)
            ->and($counts['subscribed'])->toBe(0)
            ->and($counts['unsubscribed'])->toBe(0);
    });

    test('handles partial search matches', function () {
        $list = NewsletterList::factory()->create();

        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => 'Jonathan',
            'last_name' => 'Doe',
            'email' => 'jonathan@example.com',
        ]);

        // Partial matches should work
        $partialFirstName = NewsletterSubscriber::query()->searchByName('Jon')->get();
        $partialLastName = NewsletterSubscriber::query()->searchByName('Do')->get();
        $partialEmail = NewsletterSubscriber::query()->searchByName('jonathan')->get();

        expect($partialFirstName)->toHaveCount(1)
            ->and($partialLastName)->toHaveCount(1)
            ->and($partialEmail)->toHaveCount(1);
    });

    test('date filtering handles edge cases', function () {
        $list = NewsletterList::factory()->create();
        $exactDate = now();

        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => $exactDate,
        ]);

        $afterResults = NewsletterSubscriber::query()->subscribedAfter($exactDate)->get();
        $beforeResults = NewsletterSubscriber::query()->subscribedBefore($exactDate)->get();

        // Should include the exact date in both queries
        expect($afterResults)->toHaveCount(1)
            ->and($beforeResults)->toHaveCount(1);
    });

    test('handles complex query combinations', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();

        $targetSubscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list1->id,
            'status' => 'subscribed',
            'first_name' => 'John',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'subscribed_at' => now()->subDays(1),
        ]);

        // Create noise data
        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list2->id, // Different list
            'status' => 'subscribed',
            'first_name' => 'John',
            'email_verified_at' => now(),
        ]);

        NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list1->id,
            'status' => 'unsubscribed', // Different status
            'first_name' => 'John',
            'email_verified_at' => now(),
        ]);

        $results = NewsletterSubscriber::query()
            ->whereNewsletterList($list1->id)
            ->whereSubscribed()
            ->whereVerified()
            ->searchByName('John')
            ->subscribedAfter(now()->subDays(2))
            ->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($targetSubscriber->id);
    });
});
