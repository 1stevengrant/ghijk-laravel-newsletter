<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\NewsletterList;
use Illuminate\Http\JsonResponse;
use App\Models\NewsletterSubscriber;

class PublicNewsletterController extends Controller
{
    public function subscribe(Request $request, string $shortcode): JsonResponse
    {
        $list = NewsletterList::where('shortcode', $shortcode)->firstOrFail();

        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:newsletter_subscribers,email,NULL,id,newsletter_list_id,' . $list->id,
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
        ]);

        $subscriber = NewsletterSubscriber::create([
            'newsletter_list_id' => $list->id,
            'email' => $validated['email'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'verification_token' => Str::random(60),
            'unsubscribe_token' => Str::random(60),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to ' . $list->name,
        ]);
    }

    public function show(string $shortcode)
    {
        $list = NewsletterList::where('shortcode', $shortcode)->firstOrFail();

        return view('public.newsletter.signup', compact('list'));
    }
}
