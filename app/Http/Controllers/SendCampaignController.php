<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Jobs\SendCampaignJob;
use App\Events\CampaignStatusChanged;

class SendCampaignController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Campaign $campaign)
    {
        if (! $campaign->canSend()) {
            return back()->with('error', 'Campaign cannot be sent in its current state.');
        }

        // Check if the list has any subscribed subscribers
        $subscriberCount = $campaign->newsletterList->subscribers()
            ->where('status', 'subscribed')
            ->count();

        if ($subscriberCount === 0) {
            return back()->with('error', 'Cannot send campaign to a list with no subscribers.');
        }

        // Update campaign status to sending immediately
        $previousStatus = $campaign->status;
        $campaign->update(['status' => Campaign::STATUS_SENDING]);

        // Broadcast the status change
        CampaignStatusChanged::dispatch($campaign, $previousStatus, Campaign::STATUS_SENDING);

        // Dispatch the job to send the campaign
        SendCampaignJob::dispatch($campaign);

        return redirect()->route('campaigns.index')->with('success', 'Campaign is being sent.');
    }
}
