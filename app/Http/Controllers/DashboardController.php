<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $campaignCount = Campaign::count();
        $statusCounts = Campaign::countByStatus();

        $listCount = NewsletterList::count();
        $subscriberCount = NewsletterSubscriber::where('status', 'subscribed')->count();

        return Inertia::render('dashboard', [
            'campaignCount' => $campaignCount,
            'draftCampaigns' => $statusCounts['draft'],
            'scheduledCampaigns' => $statusCounts['scheduled'],
            'sentCampaigns' => $statusCounts['sent'],
            'listCount' => $listCount,
            'subscriberCount' => $subscriberCount,
        ]);
    }
}
