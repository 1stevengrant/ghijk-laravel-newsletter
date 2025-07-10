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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NewsletterList $newsletterList)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NewsletterList $newsletterList)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, NewsletterList $list)
    {
        // Validate that the name matches (optional security check)
        $request->validate([
            'name' => 'required|string',
        ]);

        if ($request->name !== $list->name) {
            return back()->withErrors(['name' => 'The list name does not match.']);
        }

        $list->delete();

        return redirect()->route('lists.index')->with('success', 'Newsletter list deleted successfully.');
    }
}
