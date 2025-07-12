<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsletterSubscriber;

class ToggleSubscriberStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(NewsletterSubscriber $subscriber)
    {
        $newStatus = $subscriber->status === 'subscribed' ? 'unsubscribed' : 'subscribed';

        $subscriber->update([
            'status' => $newStatus,
            $newStatus === 'subscribed' ? 'subscribed_at' : 'unsubscribed_at' => now(),
        ]);

        return back()->with('success', 'Subscriber status updated.');
    }
}
