<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsletterList;
use App\Data\NewsletterListData;

class NewsletterListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('newsletter-lists', [
            'lists' => NewsletterListData::collect(NewsletterList::query()->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
        ]);

        NewsletterList::create($request->only(['name', 'description', 'from_name', 'from_email']));

        return redirect()->route('lists.index')->with('success', 'Newsletter list created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(NewsletterList $list)
    {
        $list->load(['subscribers' => function ($query) {
            $query->orderBy('subscribed_at', 'desc');
        }]);

        return inertia('newsletter-list-show', [
            'list' => NewsletterListData::from($list),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NewsletterList $list)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
        ]);

        $list->update($request->only(['name', 'description', 'from_name', 'from_email']));

        return redirect()->route('lists.index')->with('success', 'Newsletter list updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NewsletterList $list)
    {
        $list->delete();

        return redirect()->route('lists.index')->with('success', 'Newsletter list deleted successfully.');
    }
}
