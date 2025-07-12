<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;

class GenerateCampaignShortcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:generate-shortcodes {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate shortcodes for campaigns that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        // Find campaigns without shortcodes
        $campaignsWithoutShortcodes = Campaign::whereNull('shortcode')->get();

        if ($campaignsWithoutShortcodes->isEmpty()) {
            $this->info('No campaigns found without shortcodes.');

            return Command::SUCCESS;
        }

        $this->info("Found {$campaignsWithoutShortcodes->count()} campaigns without shortcodes.");

        if ($isDryRun) {
            $this->warn('DRY RUN - No changes will be made.');
            $this->table(['ID', 'Name', 'Status'], $campaignsWithoutShortcodes->map(function ($campaign) {
                return [$campaign->id, $campaign->name, $campaign->status];
            })->toArray());

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($campaignsWithoutShortcodes->count());
        $bar->start();

        $updated = 0;

        foreach ($campaignsWithoutShortcodes as $campaign) {
            try {
                $shortcode = $this->generateUniqueShortcode();
                $campaign->update(['shortcode' => $shortcode]);
                $updated++;
            } catch (\Exception $e) {
                $this->error("Failed to update campaign {$campaign->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully generated shortcodes for {$updated} campaigns.");

        return Command::SUCCESS;
    }

    /**
     * Generate a unique shortcode for campaigns.
     */
    private function generateUniqueShortcode(): string
    {
        do {
            $shortcode = mb_substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        } while (Campaign::where('shortcode', $shortcode)->exists());

        return $shortcode;
    }
}
