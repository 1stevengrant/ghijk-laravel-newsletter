<?php

namespace App\Http\Controllers\Email;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;

class TrackEmailOpenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Campaign $campaign, NewsletterSubscriber $subscriber)
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
}
