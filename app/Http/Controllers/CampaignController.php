<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Data\CampaignData;
use Illuminate\Http\Request;
use App\Jobs\SendCampaignJob;
use App\Models\NewsletterList;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $campaigns = Campaign::with(['newsletterList.subscribers' => function ($query) {
            $query->where('status', 'subscribed');
        }])->orderBy('created_at', 'desc')->get();

        return inertia('campaigns/index', [
            'campaigns' => $campaigns->map(fn ($campaign) => CampaignData::fromModel($campaign)),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lists = NewsletterList::withCount(['subscribers' => function ($query) {
            $query->where('status', 'subscribed');
        }])->get();

        return inertia('campaigns/create', [
            'lists' => $lists,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'newsletter_list_id' => 'required|exists:newsletter_lists,id',
            'status' => 'required|in:draft,scheduled',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        Campaign::create($request->only(['name', 'subject', 'content', 'newsletter_list_id', 'status', 'scheduled_at']));

        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Campaign $campaign)
    {
        $campaign->load(['newsletterList.subscribers' => function ($query) {
            $query->where('status', 'subscribed');
        }]);

        return inertia('campaigns/show', [
            'campaign' => CampaignData::fromModel($campaign),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Campaign $campaign)
    {
        $lists = NewsletterList::withCount(['subscribers' => function ($query) {
            $query->where('status', 'subscribed');
        }])->get();

        return inertia('campaigns/edit', [
            'campaign' => CampaignData::fromModel($campaign),
            'lists' => $lists,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Campaign $campaign)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'newsletter_list_id' => 'required|exists:newsletter_lists,id',
            'status' => 'required|in:draft,scheduled',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $campaign->update($request->only(['name', 'subject', 'content', 'newsletter_list_id', 'status', 'scheduled_at']));

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted successfully.');
    }

    public function send(Campaign $campaign)
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

        // Dispatch the job to send the campaign
        SendCampaignJob::dispatch($campaign);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign is being sent.');
    }
}
