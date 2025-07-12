<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
test('sent campaign can be viewed publicly', function () {
    $list = NewsletterList::factory()->create();

    $campaign = Campaign::factory()->create([
        'newsletter_list_id' => $list->id,
        'status' => 'sent',
        'shortcode' => 'ABC12345',
        'content' => '<h1>Test Campaign</h1><p>This is test content.</p>',
        'subject' => 'Test Subject',
    ]);

    $response = $this->get(route('campaign.view', $campaign->shortcode));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('campaigns/public-view')
        ->has('campaign')
        ->where('campaign.shortcode', 'ABC12345')
        ->where('campaign.subject', 'Test Subject')
        ->where('campaign.content', '<h1>Test Campaign</h1><p>This is test content.</p>')
    );
});

test('draft campaign cannot be viewed publicly', function () {
    $list = NewsletterList::factory()->create();

    $campaign = Campaign::factory()->create([
        'newsletter_list_id' => $list->id,
        'status' => 'draft',
        'shortcode' => 'ABC12345',
    ]);

    $response = $this->get(route('campaign.view', $campaign->shortcode));

    $response->assertNotFound();
});

test('invalid shortcode returns 404', function () {
    $response = $this->get(route('campaign.view', 'INVALID'));

    $response->assertNotFound();
});

test('campaign shortcode is generated automatically', function () {
    $list = NewsletterList::factory()->create();

    $campaign = Campaign::factory()->create([
        'newsletter_list_id' => $list->id,
    ]);

    expect($campaign->shortcode)->not->toBeNull();
    expect(mb_strlen($campaign->shortcode))->toBe(8);
});
