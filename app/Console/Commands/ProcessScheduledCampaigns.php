<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Jobs\SendCampaignJob;
use Illuminate\Console\Command;

class ProcessScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled campaigns that are ready to be sent';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaigns = Campaign::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns ready to be sent.');

            return;
        }

        $this->info("Processing {$campaigns->count()} scheduled campaigns...");

        foreach ($campaigns as $campaign) {
            if (! $campaign->canSend()) {
                $this->warn("Skipping campaign '{$campaign->name}' - no subscribers or invalid state");

                continue;
            }

            $this->line("Dispatching campaign: {$campaign->name}");
            SendCampaignJob::dispatch($campaign);
        }

        $this->info('All scheduled campaigns have been dispatched to the queue.');
    }
}
