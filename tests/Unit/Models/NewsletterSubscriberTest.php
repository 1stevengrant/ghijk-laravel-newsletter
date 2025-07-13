<?php

use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\QueryBuilders\NewsletterSubscriberQueryBuilder;

describe('NewsletterSubscriber Model', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    test('has no guarded attributes', function () {
        expect((new NewsletterSubscriber)->getGuarded())->toBe([]);
    });

    test('belongs to newsletter list', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        expect($subscriber->newsletterList)->toBeInstanceOf(NewsletterList::class)
            ->and($subscriber->newsletterList->id)->toBe($list->id);
    });

    test('automatically sets subscribed_at when created', function () {
        $beforeTime = now();

        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => null, // Should be auto-set
        ]);

        $afterTime = now();

        expect($subscriber->subscribed_at)->not->toBeNull()
            ->and($subscriber->subscribed_at->gte($beforeTime))->toBeTrue()
            ->and($subscriber->subscribed_at->lte($afterTime))->toBeTrue();
    });

    test('does not override existing subscribed_at', function () {
        $customTime = now()->subDays(5);

        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'subscribed_at' => $customTime,
        ]);

        expect($subscriber->subscribed_at->eq($customTime))->toBeTrue();
    });

    test('automatically generates verification token when created', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'verification_token' => null, // Should be auto-generated
        ]);

        expect($subscriber->verification_token)->not->toBeNull()
            ->and($subscriber->verification_token)->toBeString()
            ->and(mb_strlen($subscriber->verification_token))->toBe(36);
        // UUID length
    });

    test('does not override existing verification token', function () {
        $customToken = 'custom-verification-token';

        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'verification_token' => $customToken,
        ]);

        expect($subscriber->verification_token)->toBe($customToken);
    });

    test('automatically generates unsubscribe token when created', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'unsubscribe_token' => null, // Should be auto-generated
        ]);

        expect($subscriber->unsubscribe_token)->not->toBeNull()
            ->and($subscriber->unsubscribe_token)->toBeString()
            ->and(mb_strlen($subscriber->unsubscribe_token))->toBe(36);
        // UUID length
    });

    test('does not override existing unsubscribe token', function () {
        $customToken = 'custom-unsubscribe-token';

        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'unsubscribe_token' => $customToken,
        ]);

        expect($subscriber->unsubscribe_token)->toBe($customToken);
    });

    test('generates unique tokens for different subscribers', function () {
        $list = NewsletterList::factory()->create();

        $subscriber1 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);
        $subscriber2 = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
        ]);

        expect($subscriber1->verification_token)->not->toBe($subscriber2->verification_token)
            ->and($subscriber1->unsubscribe_token)->not->toBe($subscriber2->unsubscribe_token);
    });

    test('name attribute returns full name when both first and last name exist', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        expect($subscriber->name)->toBe('John Doe');
    });

    test('name attribute returns first name only when last name is null', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => 'John',
            'last_name' => null,
            'email' => 'john@example.com',
        ]);

        expect($subscriber->name)->toBe('John');
    });

    test('name attribute returns last name only when first name is null', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => null,
            'last_name' => 'Doe',
            'email' => 'doe@example.com',
        ]);

        expect($subscriber->name)->toBe('Doe');
    });

    test('name attribute returns email when both names are null', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => null,
            'last_name' => null,
            'email' => 'unknown@example.com',
        ]);

        expect($subscriber->name)->toBe('unknown@example.com');
    });

    test('name attribute returns email when both names are empty strings', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => '',
            'last_name' => '',
            'email' => 'empty@example.com',
        ]);

        expect($subscriber->name)->toBe('empty@example.com');
    });

    test('name attribute handles whitespace correctly', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'first_name' => '  John  ',
            'last_name' => '  Doe  ',
            'email' => 'john@example.com',
        ]);

        expect($subscriber->name)->toContain('John')
            ->and($subscriber->name)->toContain('Doe');
        // The exact spacing might vary due to trim() behavior
    });

    test('uses custom query builder', function () {
        $query = NewsletterSubscriber::query();
        expect($query)->toBeInstanceOf(NewsletterSubscriberQueryBuilder::class);
    });

    test('can store different subscriber statuses', function () {
        $list = NewsletterList::factory()->create();

        $subscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
        ]);

        $unsubscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
        ]);

        expect($subscribedUser->status)->toBe('subscribed')
            ->and($unsubscribedUser->status)->toBe('unsubscribed');
    });

    test('can be created with minimal required fields', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::create([
            'newsletter_list_id' => $list->id,
            'email' => 'minimal@example.com',
            'subscribed_at' => now(),
        ]);

        expect($subscriber->email)->toBe('minimal@example.com')
            ->and($subscriber->newsletter_list_id)->toBe($list->id)
            ->and($subscriber->verification_token)->not->toBeNull()
            ->and($subscriber->unsubscribe_token)->not->toBeNull()
            ->and($subscriber->subscribed_at)->not->toBeNull();
    });

    test('handles optional fields correctly', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email' => 'optional@example.com',
            'first_name' => 'Optional',
            'last_name' => 'User',
            'email_verified_at' => now(),
            'unsubscribed_at' => null,
        ]);

        expect($subscriber->first_name)->toBe('Optional')
            ->and($subscriber->last_name)->toBe('User')
            ->and($subscriber->email_verified_at)->not->toBeNull()
            ->and($subscriber->unsubscribed_at)->toBeNull();
    });

    test('handles unsubscribed_at timestamp', function () {
        $list = NewsletterList::factory()->create();
        $unsubscribeTime = now();

        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
            'unsubscribed_at' => $unsubscribeTime,
        ]);

        expect($subscriber->unsubscribed_at)->not->toBeNull()
            ->and($subscriber->unsubscribed_at->eq($unsubscribeTime))->toBeTrue();
    });

    test('can store email verification data', function () {
        $list = NewsletterList::factory()->create();
        $verificationTime = now();

        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email_verified_at' => $verificationTime,
            'verification_token' => 'verified-token',
        ]);

        expect($subscriber->email_verified_at)->not->toBeNull()
            ->and($subscriber->email_verified_at->eq($verificationTime))->toBeTrue()
            ->and($subscriber->verification_token)->toBe('verified-token');
    });

    test('handles subscriber lifecycle correctly', function () {
        $list = NewsletterList::factory()->create();

        // Create new subscriber
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'email_verified_at' => null,
            'unsubscribed_at' => null,
        ]);

        // Verify email
        $subscriber->update(['email_verified_at' => now()]);
        expect($subscriber->email_verified_at)->not->toBeNull();

        // Unsubscribe
        $subscriber->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
        expect($subscriber->status)->toBe('unsubscribed')
            ->and($subscriber->unsubscribed_at)->not->toBeNull();
    });

    test('stores complete subscriber information', function () {
        $list = NewsletterList::factory()->create();
        $subscriber = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'email' => 'complete@example.com',
            'first_name' => 'Complete',
            'last_name' => 'User',
            'status' => 'subscribed',
            'email_verified_at' => now()->subDays(1),
            'verification_token' => 'complete-verification-token',
            'unsubscribe_token' => 'complete-unsubscribe-token',
            'subscribed_at' => now()->subDays(2),
            'unsubscribed_at' => null,
        ]);

        expect($subscriber->email)->toBe('complete@example.com')
            ->and($subscriber->first_name)->toBe('Complete')
            ->and($subscriber->last_name)->toBe('User')
            ->and($subscriber->name)->toBe('Complete User')
            ->and($subscriber->status)->toBe('subscribed')
            ->and($subscriber->verification_token)->toBe('complete-verification-token')
            ->and($subscriber->unsubscribe_token)->toBe('complete-unsubscribe-token')
            ->and($subscriber->email_verified_at)->not->toBeNull()
            ->and($subscriber->subscribed_at)->not->toBeNull()
            ->and($subscriber->unsubscribed_at)->toBeNull();
    });
});
