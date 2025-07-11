<?php

namespace App\Http\Controllers\Email;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;

class UnsubscribeEmailController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $token = $request->query('token');
        $campaignId = $request->query('campaign');

        if (! $token) {
            abort(404);
        }

        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (! $subscriber) {
            abort(404);
        }

        // Update subscriber status to unsubscribed
        $subscriber->update(['status' => 'unsubscribed']);

        // If campaign ID is provided, increment the unsubscribe count for that campaign
        if ($campaignId) {
            $campaign = Campaign::find($campaignId);
            if ($campaign) {
                $campaign->increment('unsubscribes');
            }
        }

        return view('emails.unsubscribed', compact('subscriber'));
    }
}
