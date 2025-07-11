<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Models\NewsletterSubscriber;

class EmailTrackingController extends Controller
{
    /**
     * Track email opens via tracking pixel
     */
    public function trackOpen(Request $request, Campaign $campaign, NewsletterSubscriber $subscriber)
    {
        // Track unique opens - only insert if this combination doesn't exist
        $wasInserted = DB::table('campaign_opens')->insertOrIgnore([
            'campaign_id' => $campaign->id,
            'newsletter_subscriber_id' => $subscriber->id,
            'opened_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Only increment the campaign opens count if this is a new unique open
        if ($wasInserted) {
            $campaign->increment('opens');
        }

        // Create a 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => mb_strlen($pixel),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Track email clicks
     */
    public function trackClick(Request $request, Campaign $campaign, NewsletterSubscriber $subscriber)
    {
        // Increment click count for the campaign
        $campaign->increment('clicks');

        // Redirect to the original URL if provided
        $url = $request->query('url');
        if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
            return redirect()->away($url);
        }

        // If no URL provided, return a simple response
        return response('Link tracked', 200);
    }

    /**
     * Handle unsubscribe requests
     */
    public function unsubscribe(Request $request)
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
