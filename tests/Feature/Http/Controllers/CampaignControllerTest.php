<?php

use App\Models\User;
use App\Models\Campaign;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use App\Events\CampaignStatusChanged;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->list = NewsletterList::factory()->create();
});

describe('index', function () {
    test('displays campaigns with newsletter list data', function () {
        $campaign1 = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'created_at' => now()->subMinute(),
        ]);
        $campaign2 = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'created_at' => now(),
        ]);

        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed',
        ]);

        $response = $this->get(route('campaigns.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('campaigns/index')
                ->has('campaigns', 2)
                ->has('campaigns.0', fn (Assert $campaign) => $campaign
                    ->where('id', $campaign2->id) // Should be ordered by created_at desc
                    ->has('newsletter_list')
                    ->where('newsletter_list.subscribers_count', 3)
                    ->etc()
                )
            );
    });

    test('orders campaigns by creation date descending', function () {
        $oldCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'created_at' => now()->subDay(),
        ]);
        $newCampaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'created_at' => now(),
        ]);

        $response = $this->get(route('campaigns.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.0', fn (Assert $campaign) => $campaign
                    ->where('id', $newCampaign->id)
                    ->etc()
                )
                ->has('campaigns.1', fn (Assert $campaign) => $campaign
                    ->where('id', $oldCampaign->id)
                    ->etc()
                )
            );
    });

    test('only counts subscribed subscribers', function () {
        $campaign = Campaign::factory()->create(['newsletter_list_id' => $this->list->id]);

        NewsletterSubscriber::factory()->count(2)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed',
        ]);
        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'unsubscribed',
        ]);

        $response = $this->get(route('campaigns.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('campaigns.0.newsletter_list', fn (Assert $list) => $list
                    ->where('subscribers_count', 2)
                    ->etc()
                )
            );
    });

    test('redirects guests to login', function () {
        auth()->logout();

        $response = $this->get(route('campaigns.index'));

        $response->assertRedirect(route('login'));
    });
});

describe('create', function () {
    test('displays campaign creation form with newsletter lists', function () {
        NewsletterSubscriber::factory()->count(2)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed',
        ]);

        $list2 = NewsletterList::factory()->create();

        $response = $this->get(route('campaigns.create'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('campaigns/create')
                ->has('lists', 2)
                ->has('lists.0', fn (Assert $list) => $list
                    ->where('id', $this->list->id)
                    ->where('subscribers_count', 2)
                    ->etc()
                )
            );
    });
});

describe('store', function () {
    test('creates a new campaign with valid data', function () {
        $data = [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'newsletter_list_id' => $this->list->id,
        ];

        $response = $this->post(route('campaigns.store'), $data);

        $this->assertDatabaseHas('campaigns', array_merge($data, [
            'status' => 'draft',
            'content' => '',
        ]));

        $campaign = Campaign::where('name', 'Test Campaign')->first();
        $response->assertRedirect(route('campaigns.content', $campaign))
            ->assertSessionHas('success', 'Campaign created! Now add your content.');
    });

    test('validates required fields', function () {
        $response = $this->post(route('campaigns.store'), []);

        $response->assertSessionHasErrors(['name', 'newsletter_list_id']);
        $this->assertDatabaseCount('campaigns', 0);
    });

    test('validates newsletter list exists', function () {
        $data = [
            'name' => 'Test Campaign',
            'newsletter_list_id' => 999,
        ];

        $response = $this->post(route('campaigns.store'), $data);

        $response->assertSessionHasErrors(['newsletter_list_id']);
        $this->assertDatabaseCount('campaigns', 0);
    });

    test('allows nullable subject', function () {
        $data = [
            'name' => 'Test Campaign',
            'newsletter_list_id' => $this->list->id,
            'subject' => null,
        ];

        $response = $this->post(route('campaigns.store'), $data);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'subject' => null,
        ]);
    });

    test('validates field lengths', function () {
        $data = [
            'name' => str_repeat('a', 256),
            'subject' => str_repeat('b', 256),
            'newsletter_list_id' => $this->list->id,
        ];

        $response = $this->post(route('campaigns.store'), $data);

        $response->assertSessionHasErrors(['name', 'subject']);
    });
});

describe('content', function () {
    test('displays content editing form for editable campaign', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
        ]);

        $response = $this->get(route('campaigns.content', $campaign));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('campaigns/content')
                ->has('campaign', fn (Assert $campaignData) => $campaignData
                    ->where('id', $campaign->id)
                    ->where('status', 'draft')
                    ->etc()
                )
            );
    });

    test('redirects when trying to edit sent campaign', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'sent',
        ]);

        $response = $this->get(route('campaigns.content', $campaign));

        $response->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('error', 'Cannot edit a campaign that has already been sent.');
    });
});

describe('updateContent', function () {
    test('updates campaign content and status', function () {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
        ]);

        $data = [
            'content' => 'Updated content',
            'blocks' => ['block1', 'block2'],
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
        ];

        $response = $this->put(route('campaigns.content.update', $campaign), $data);

        $response->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('success', 'Campaign content updated successfully.');

        $campaign->refresh();
        expect($campaign->content)->toBe('Updated content')
            ->and($campaign->blocks)->toBe(['block1', 'block2'])
            ->and($campaign->status)->toBe('scheduled');

        Event::assertDispatched(CampaignStatusChanged::class);
    });

    test('does not dispatch event if status unchanged', function () {
        Event::fake();

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
        ]);

        $data = [
            'content' => 'Updated content',
            'status' => 'draft',
        ];

        $response = $this->put(route('campaigns.content.update', $campaign), $data);

        $response->assertRedirect();
        Event::assertNotDispatched(CampaignStatusChanged::class);
    });

    test('validates required fields', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
        ]);

        $response = $this->put(route('campaigns.content.update', $campaign), []);

        $response->assertSessionHasErrors(['status']);
    });

    test('validates status values', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
        ]);

        $data = ['status' => 'invalid-status'];

        $response = $this->put(route('campaigns.content.update', $campaign), $data);

        $response->assertSessionHasErrors(['status']);
    });

    test('validates scheduled date is in future', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
        ]);

        $data = [
            'status' => 'scheduled',
            'scheduled_at' => now()->subHour()->toDateTimeString(),
        ];

        $response = $this->put(route('campaigns.content.update', $campaign), $data);

        $response->assertSessionHasErrors(['scheduled_at']);
    });

    test('prevents editing sent campaigns', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'sent',
        ]);

        $data = ['content' => 'New content', 'status' => 'draft'];

        $response = $this->put(route('campaigns.content.update', $campaign), $data);

        $response->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('error', 'Cannot edit a campaign that has already been sent.');
    });
});

describe('show', function () {
    test('displays campaign with newsletter list and subscribers', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
        ]);

        NewsletterSubscriber::factory()->count(3)->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'subscribed',
        ]);

        $response = $this->get(route('campaigns.show', $campaign));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('campaigns/show')
                ->has('campaign', fn (Assert $campaignData) => $campaignData
                    ->where('id', $campaign->id)
                    ->has('newsletter_list')
                    ->where('newsletter_list.subscribers.0.status', 'subscribed')
                    ->etc()
                )
            );
    });

    test('returns 404 for non-existent campaign', function () {
        $response = $this->get(route('campaigns.show', 999));

        $response->assertNotFound();
    });
});

describe('destroy', function () {
    test('deletes draft campaign', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
        ]);

        $response = $this->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns.index'))
            ->assertSessionHas('success', 'Campaign deleted successfully.');

        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    });

    test('prevents deleting non-draft campaigns', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'sent',
        ]);

        $response = $this->delete(route('campaigns.destroy', $campaign));

        $response->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('error', 'Only draft campaigns can be deleted.');

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id]);
    });
});

describe('view (public)', function () {
    test('displays public campaign view for sent campaigns', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'sent',
            'shortcode' => 'test-shortcode',
            'sent_at' => now(),
        ]);

        $response = $this->get(route('campaign.view', $campaign->shortcode));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('campaigns/public-view')
                ->has('campaign', fn (Assert $campaignData) => $campaignData
                    ->where('shortcode', 'test-shortcode')
                    ->where('name', $campaign->name)
                    ->has('sent_at')
                    ->etc()
                )
            );
    });

    test('returns 404 for non-sent campaigns', function () {
        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'draft',
            'shortcode' => 'test-shortcode',
        ]);

        $response = $this->get(route('campaign.view', $campaign->shortcode));

        $response->assertNotFound();
    });

    test('returns 404 for non-existent shortcode', function () {
        $response = $this->get(route('campaign.view', 'non-existent'));

        $response->assertNotFound();
    });

    test('public view does not require authentication', function () {
        auth()->logout();

        $campaign = Campaign::factory()->create([
            'newsletter_list_id' => $this->list->id,
            'status' => 'sent',
            'shortcode' => 'test-shortcode',
            'sent_at' => now(),
        ]);

        $response = $this->get(route('campaign.view', $campaign->shortcode));

        $response->assertOk();
    });
});
