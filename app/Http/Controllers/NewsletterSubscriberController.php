<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Data\NewsletterSubscribersData;

class NewsletterSubscriberController extends Controller
{
    public function index()
    {
        return inertia('newsletter-subscribers', [
            'newsletterSubscribers' => NewsletterSubscribersData::collect(NewsletterSubscriber::query()->get()),
        ]);
    }

    public function destroy(NewsletterSubscriber $newsletterSubscriber)
    {
        $newsletterSubscriber->delete();

        return to_route('subscribers.index')->with('success', 'Subscriber deleted.');
    }
}
