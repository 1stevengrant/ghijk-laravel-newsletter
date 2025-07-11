<?php

namespace App\Http\Controllers\Email;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;

class TrackEmailClickController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Campaign $campaign, NewsletterSubscriber $subscriber)
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
}
