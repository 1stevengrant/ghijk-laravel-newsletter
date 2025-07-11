<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsletterList;
use App\Models\NewsletterSubscriber;

class NewsletterSubscriberController extends Controller
{
    public function store(NewsletterList $list, Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:newsletter_subscribers,email,NULL,id,newsletter_list_id,' . $list->id,
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
        ]);

        $validated['newsletter_list_id'] = $list->id;
        NewsletterSubscriber::create($validated);

        return to_route('lists.show', $list)->with('success', 'Subscriber created.');
    }

    public function destroy(NewsletterList $list, NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        return to_route('lists.show', $list)->with('success', 'Subscriber deleted.');
    }
}
