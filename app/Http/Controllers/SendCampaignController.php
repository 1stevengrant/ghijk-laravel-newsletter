<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Jobs\SendCampaignJob;

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
        $campaign->update(['status' => 'sending']);

        // Dispatch the job to send the campaign
        SendCampaignJob::dispatch($campaign);

        return redirect()->route('campaigns.index')->with('success', 'Campaign is being sent.');
    }
}
