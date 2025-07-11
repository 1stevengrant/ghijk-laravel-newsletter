<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Mail\CampaignEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

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
        $this->campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $sentCount,
            'bounces' => $bounces,
        ]);
    }
}
