<?php

namespace App\Http\Controllers;

use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterSubscriberController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'newsletter_list_id' => 'required|integer|exists:newsletter_lists,id'
        ]);

        NewsletterSubscriber::create($validated);

        return to_route('lists.show', $validated['newsletter_list_id'])->with('success', 'Subscriber created.');
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        return to_route('lists.show', $subscriber->newsletter_list_id)->with('success', 'Subscriber deleted.');
    }
}
