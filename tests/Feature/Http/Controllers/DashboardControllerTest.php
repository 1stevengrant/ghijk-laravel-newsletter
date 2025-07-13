<?php

use App\Models\User;
use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('dashboard', function () {
    test('displays dashboard with campaign statistics', function () {
        // Create campaigns with different statuses
        $list = NewsletterList::factory()->create();
        
        Campaign::factory()->count(2)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'draft'
        ]);
        Campaign::factory()->count(3)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'scheduled'
        ]);
        Campaign::factory()->count(1)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'sent'
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('campaignCount', 6)
                ->where('draftCampaigns', 2)
                ->where('scheduledCampaigns', 3)
                ->where('sentCampaigns', 1)
            );
    });

    test('displays newsletter list count', function () {
        NewsletterList::factory()->count(5)->create();

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('listCount', 5)
            );
    });

    test('displays subscribed subscriber count only', function () {
        $list = NewsletterList::factory()->create();
        
        // Create subscribed subscribers
        NewsletterSubscriber::factory()->count(8)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'subscribed'
        ]);
        
        // Create unsubscribed subscribers (should not be counted)
        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $list->id,
            'status' => 'unsubscribed'
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('subscriberCount', 8)
            );
    });

    test('displays zero counts when no data exists', function () {
        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('campaignCount', 0)
                ->where('draftCampaigns', 0)
                ->where('scheduledCampaigns', 0)
                ->where('sentCampaigns', 0)
                ->where('listCount', 0)
                ->where('subscriberCount', 0)
            );
    });

    test('handles mixed campaign statuses correctly', function () {
        $list = NewsletterList::factory()->create();
        
        Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'draft'
        ]);
        Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'sending'
        ]);
        Campaign::factory()->create([
            'newsletter_list_id' => $list->id,
            'status' => 'sent'
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('campaignCount', 3)
                ->where('draftCampaigns', 1)
                ->where('scheduledCampaigns', 0) // No scheduled campaigns
                ->where('sentCampaigns', 1) // 'sending' status is not counted as 'sent'
            );
    });

    test('counts campaigns across multiple newsletter lists', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();
        
        Campaign::factory()->count(2)->create([
            'newsletter_list_id' => $list1->id,
            'status' => 'draft'
        ]);
        Campaign::factory()->count(3)->create([
            'newsletter_list_id' => $list2->id,
            'status' => 'draft'
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('campaignCount', 5)
                ->where('draftCampaigns', 5)
                ->where('listCount', 2)
            );
    });

    test('counts subscribers across multiple newsletter lists', function () {
        $list1 = NewsletterList::factory()->create();
        $list2 = NewsletterList::factory()->create();
        
        NewsletterSubscriber::factory()->count(4)->create([
            'newsletter_list_id' => $list1->id,
            'status' => 'subscribed'
        ]);
        NewsletterSubscriber::factory()->count(6)->create([
            'newsletter_list_id' => $list2->id,
            'status' => 'subscribed'
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('subscriberCount', 10)
                ->where('listCount', 2)
            );
    });

    test('handles large numbers correctly', function () {
        $lists = NewsletterList::factory()->count(100)->create();
        
        // Create campaigns for first few lists
        foreach ($lists->take(10) as $list) {
            Campaign::factory()->count(5)->create([
                'newsletter_list_id' => $list->id,
                'status' => 'sent'
            ]);
        }
        
        // Create subscribers for all lists
        foreach ($lists as $list) {
            NewsletterSubscriber::factory()->count(50)->create([
                'newsletter_list_id' => $list->id,
                'status' => 'subscribed'
            ]);
        }

        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('campaignCount', 50)
                ->where('sentCampaigns', 50)
                ->where('listCount', 100)
                ->where('subscriberCount', 5000)
            );
    });

    test('requires authentication', function () {
        auth()->logout();

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });

    test('returns correct structure with all required data', function () {
        $response = $this->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('campaignCount')
                ->has('draftCampaigns')
                ->has('scheduledCampaigns')
                ->has('sentCampaigns')
                ->has('listCount')
                ->has('subscriberCount')
                ->whereType('campaignCount', 'integer')
                ->whereType('draftCampaigns', 'integer')
                ->whereType('scheduledCampaigns', 'integer')
                ->whereType('sentCampaigns', 'integer')
                ->whereType('listCount', 'integer')
                ->whereType('subscriberCount', 'integer')
            );
    });
});