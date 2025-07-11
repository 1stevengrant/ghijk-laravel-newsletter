<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Mail\CampaignEmail;
use Illuminate\Support\Facades\Mail;
use App\Events\CampaignStatusChanged;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use romanzipp\QueueMonitor\Traits\IsMonitored;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, IsMonitored, Queueable, SerializesModels;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Add artificial delay in development for visibility
        if (app()->environment('local')) {
            sleep(config('newsletters.campaign_send_delay'));
        }

        $subscribers = $this->campaign->newsletterList->subscribers()
            ->where('status', 'subscribed')
            ->get();

        $sentCount = 0;
        $bounces = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(new CampaignEmail($this->campaign, $subscriber));
                $sentCount++;
            } catch (\Exception $e) {
                $bounces++;
                \Log::error('Failed to send campaign email', [
                    'campaign_id' => $this->campaign->id,
                    'subscriber_email' => $subscriber->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update campaign with final stats
        $previousStatus = $this->campaign->status;
        $this->campaign->update([
            'status' => Campaign::STATUS_SENT,
            'sent_at' => now(),
            'sent_count' => $sentCount,
            'bounces' => $bounces,
        ]);

        // Broadcast the status change
        CampaignStatusChanged::dispatch($this->campaign, $previousStatus, Campaign::STATUS_SENT);
    }
}
