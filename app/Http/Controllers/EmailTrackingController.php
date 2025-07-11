<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\NewsletterSubscriber;

class EmailTrackingController extends Controller
{
    /**
     * Track email opens via tracking pixel
     */
    public function trackOpen(Campaign $campaign, NewsletterSubscriber $subscriber)
    {
        // Increment open count for the campaign
        $campaign->increment('opens');

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

        if (! $token) {
            abort(404);
        }

        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (! $subscriber) {
            abort(404);
        }

        // Update subscriber status to unsubscribed
        $subscriber->update(['status' => 'unsubscribed']);

        return view('emails.unsubscribed', compact('subscriber'));
    }
}
