<?php

use App\Models\Campaign;
use App\Models\NewsletterList;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command generates shortcodes for campaigns without them', function () {
    $list = NewsletterList::factory()->create();

    // Create campaigns without shortcodes by directly inserting
    $campaign1 = Campaign::factory()->create(['newsletter_list_id' => $list->id]);
    $campaign2 = Campaign::factory()->create(['newsletter_list_id' => $list->id]);

    // Remove shortcodes to simulate old campaigns
    Campaign::whereIn('id', [$campaign1->id, $campaign2->id])->update(['shortcode' => null]);

    $this->artisan('campaigns:generate-shortcodes')
        ->expectsOutput('Found 2 campaigns without shortcodes.')
        ->expectsOutput('Successfully generated shortcodes for 2 campaigns.')
        ->assertSuccessful();

    // Verify shortcodes were generated
    $campaign1->refresh();
    $campaign2->refresh();

    expect($campaign1->shortcode)->not->toBeNull();
    expect($campaign2->shortcode)->not->toBeNull();
    expect(mb_strlen($campaign1->shortcode))->toBe(8);
    expect(mb_strlen($campaign2->shortcode))->toBe(8);
    expect($campaign1->shortcode)->not->toBe($campaign2->shortcode);
});

test('command shows dry run without making changes', function () {
    $list = NewsletterList::factory()->create();

    $campaign = Campaign::factory()->create(['newsletter_list_id' => $list->id]);
    Campaign::where('id', $campaign->id)->update(['shortcode' => null]);

    $this->artisan('campaigns:generate-shortcodes --dry-run')
        ->expectsOutput('Found 1 campaigns without shortcodes.')
        ->expectsOutput('DRY RUN - No changes will be made.')
        ->assertSuccessful();

    // Verify no changes were made
    $campaign->refresh();
    expect($campaign->shortcode)->toBeNull();
});

test('command handles no campaigns without shortcodes', function () {
    $this->artisan('campaigns:generate-shortcodes')
        ->expectsOutput('No campaigns found without shortcodes.')
        ->assertSuccessful();
});
