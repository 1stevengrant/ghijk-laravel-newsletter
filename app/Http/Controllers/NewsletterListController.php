<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsletterList;
use App\Data\NewsletterListsData;

class NewsletterListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('newsletter-lists', [
            'lists' => NewsletterListsData::collect(NewsletterList::query()->get()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
        ]);

        NewsletterList::create($request->only(['name', 'from_name', 'from_email']));

        return redirect()->route('lists.index')->with('success', 'Newsletter list created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(NewsletterList $newsletterList)
    {
        return inertia('newsletter-list-show', [
            'list' => NewsletterListsData::from($newsletterList),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NewsletterList $list)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email|max:255',
        ]);

        $list->update($request->only(['name', 'from_name', 'from_email']));

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
