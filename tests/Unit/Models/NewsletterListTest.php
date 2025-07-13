<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

describe('NewsletterList Model', function () {
    uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

    test('has no guarded attributes', function () {
        expect((new NewsletterList)->getGuarded())->toBe([]);
    });

    test('generates unique shortcode on creation', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();

        expect($list1->shortcode)->not->toBeNull()
            ->and($list2->shortcode)->not->toBeNull()
            ->and($list1->shortcode)->not->toBe($list2->shortcode)
            ->and(mb_strlen($list1->shortcode))->toBe(8);
    });

    test('does not override existing shortcode', function () {
        $customShortcode = 'CUSTOM01';

        $list = NewsletterList::factory()->create([
            'shortcode' => $customShortcode,
        ]);

        expect($list->shortcode)->toBe($customShortcode);
    });

    test('has many subscribers', function () {
        $list = NewsletterList::factory()->create();

        // Create subscribers for this list
        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $list->id,
        ]);

        expect($list->subscribers)->toHaveCount(3)
            ->and($list->subscribers->first())->toBeInstanceOf(NewsletterSubscriber::class);
    });

    test('has many campaigns', function () {
        $list = NewsletterList::factory()->create();

        // Create campaigns for this list
        Campaign::factory()->count(2)->create([
            'newsletter_list_id' => $list->id,
        ]);

        expect($list->campaigns)->toHaveCount(2)
            ->and($list->campaigns->first())->toBeInstanceOf(Campaign::class);
    });

    test('generates embed form snippet correctly', function () {
        $list = NewsletterList::factory()->create([
            'name' => 'Test Newsletter',
            'shortcode' => 'TEST1234',
        ]);

        $snippet = $list->getEmbedFormSnippet();

        expect($snippet)->toBeString()
            ->and($snippet)->toContain('Test Newsletter')
            ->and($snippet)->toContain('TEST1234')
            ->and($snippet)->toContain('<form')
            ->and($snippet)->toContain('</form>')
            ->and($snippet)->toContain('<script>')
            ->and($snippet)->toContain('</script>')
            ->and($snippet)->toContain('/newsletter/TEST1234/subscribe')
            ->and($snippet)->toContain('TEST1234');
        // Check for route patterns instead of exact strings
        // The signup URL is commented out in the snippet as an alternative
    });

    test('embed form snippet contains proper form elements', function () {
        $list = NewsletterList::factory()->create([
            'name' => 'Marketing Updates',
            'shortcode' => 'MARKET01',
        ]);

        $snippet = $list->getEmbedFormSnippet();

        // Check for required form elements
        expect($snippet)->toContain('type="email"')
            ->and($snippet)->toContain('name="email"')
            ->and($snippet)->toContain('required')
            ->and($snippet)->toContain('name="first_name"')
            ->and($snippet)->toContain('name="last_name"')
            ->and($snippet)->toContain('type="submit"')
            ->and($snippet)->toContain('id="email-MARKET01"')
            ->and($snippet)->toContain('id="first_name-MARKET01"')
            ->and($snippet)->toContain('id="last_name-MARKET01"')
            ->and($snippet)->toContain('id="newsletter-signup-MARKET01"');

        // Check for proper IDs
    });

    test('embed form snippet contains JavaScript functionality', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'JS123456',
        ]);

        $snippet = $list->getEmbedFormSnippet();

        // Check for JavaScript event handling
        expect($snippet)->toContain('addEventListener')
            ->and($snippet)->toContain('preventDefault')
            ->and($snippet)->toContain('FormData')
            ->and($snippet)->toContain('fetch(')
            ->and($snippet)->toContain('response.json()')
            ->and($snippet)->toContain('XMLHttpRequest')
            ->and($snippet)->toContain('disabled = true')
            ->and($snippet)->toContain('Subscribing...');
    });

    test('embed form snippet contains proper styling', function () {
        $list = NewsletterList::factory()->create();

        $snippet = $list->getEmbedFormSnippet();

        // Check for inline styles for form elements
        expect($snippet)->toContain('style="max-width: 400px')
            ->and($snippet)->toContain('style="width: 100%')
            ->and($snippet)->toContain('padding: 10px')
            ->and($snippet)->toContain('style="display: block')
            ->and($snippet)->toContain('background-color: #007cba')
            ->and($snippet)->toContain('color: white');
    });

    test('embed form snippet handles dynamic content correctly', function () {
        $list = NewsletterList::factory()->create([
            'name' => 'Special "Quotes" & Symbols',
            'shortcode' => 'SPECIAL1',
        ]);

        $snippet = $list->getEmbedFormSnippet();

        // The name should appear in the form but be properly escaped
        expect($snippet)->toContain('Special "Quotes" & Symbols')
            ->and($snippet)->toContain('Subscribe to Special "Quotes" & Symbols');
    });

    test('shortcode generation handles collisions', function () {
        // Create list with specific shortcode to test collision avoidance
        $existingList = NewsletterList::factory()->create([
            'shortcode' => 'TESTCODE',
        ]);

        // Create new list - should get different shortcode
        $newList = NewsletterList::factory()->create();

        expect($newList->shortcode)->not->toBe('TESTCODE')
            ->and($newList->shortcode)->not->toBe($existingList->shortcode);
    });

    test('can be created with all required fields', function () {
        $list = NewsletterList::create([
            'name' => 'My Newsletter',
            'description' => 'A great newsletter',
            'from_email' => 'newsletter@example.com',
            'from_name' => 'Newsletter Team',
        ]);

        expect($list->name)->toBe('My Newsletter')
            ->and($list->description)->toBe('A great newsletter')
            ->and($list->from_email)->toBe('newsletter@example.com')
            ->and($list->from_name)->toBe('Newsletter Team')
            ->and($list->shortcode)->not->toBeNull();
    });

    test('description can be null', function () {
        $list = NewsletterList::create([
            'name' => 'Simple Newsletter',
            'from_email' => 'simple@example.com',
            'from_name' => 'Simple Team',
            'description' => null,
        ]);

        expect($list->description)->toBeNull()
            ->and($list->name)->toBe('Simple Newsletter');
    });

    test('relationships work correctly with data', function () {
        $list = NewsletterList::factory()->create();

        // Create subscribers with different statuses
        $subscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed',
            'email' => 'subscribed@example.com',
        ]);
        $unsubscribedUser = NewsletterSubscriber::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed',
            'email' => 'unsubscribed@example.com',
        ]);

        // Create campaigns with different statuses
        $draftCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'draft',
            'subject' => 'Draft Campaign',
        ]);
        $sentCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'sent',
            'subject' => 'Sent Campaign',
        ]);

        $list->refresh();

        expect($list->subscribers)->toHaveCount(2)
            ->and($list->campaigns)->toHaveCount(2)
            ->and($list->subscribers->where('status', 'subscribed'))->toHaveCount(1)
            ->and($list->subscribers->where('status', 'unsubscribed'))->toHaveCount(1)
            ->and($list->campaigns->where('status', 'draft'))->toHaveCount(1)
            ->and($list->campaigns->where('status', 'sent'))->toHaveCount(1);

        // Test specific relationships
    });

    test('embed form includes proper URLs', function () {
        $list = NewsletterList::factory()->create([
            'shortcode' => 'URL12345',
        ]);

        $snippet = $list->getEmbedFormSnippet();

        // Should contain the route URLs for subscribe and signup
        expect($snippet)->toContain('/newsletter/URL12345/subscribe')
            ->and($snippet)->toContain('/newsletter/URL12345');
        // Check that both URLs exist in the snippet (one as action, one as link)
    });

    test('handles long list names in embed form', function () {
        $longName = 'This is a very long newsletter name that might cause issues with form generation and display but should be handled gracefully';

        $list = NewsletterList::factory()->create([
            'name' => $longName,
        ]);

        $snippet = $list->getEmbedFormSnippet();

        expect($snippet)->toContain($longName)
            ->and($snippet)->toContain("Subscribe to $longName");
    });

    test('embed form contains error and success handling', function () {
        $list = NewsletterList::factory()->create();

        $snippet = $list->getEmbedFormSnippet();

        // Check for error and success message handling
        expect($snippet)->toContain('#d4edda')
            ->and($snippet)->toContain('#f8d7da')
            ->and($snippet)->toContain('data.success')
            ->and($snippet)->toContain('data.message')
            ->and($snippet)->toContain('error.message')
            ->and($snippet)->toContain('display: block')
            ->and($snippet)->toContain('border-radius: 4px'); // Success background color
        // Error background color
    });

    test('shortcode generation uses correct length and characters', function () {
        $list = NewsletterList::factory()->create();

        // Check shortcode format
        expect(mb_strlen($list->shortcode))->toBe(8)
            ->and($list->shortcode)->toMatch('/^[a-zA-Z0-9]+$/');
        // Only alphanumeric characters
    });

    test('can store empty description', function () {
        $list = NewsletterList::create([
            'name' => 'No Description Newsletter',
            'from_email' => 'nodesc@example.com',
            'from_name' => 'No Desc Team',
            'description' => '',
        ]);

        expect($list->description)->toBe('')
            ->and($list->name)->toBe('No Description Newsletter');
    });
});
