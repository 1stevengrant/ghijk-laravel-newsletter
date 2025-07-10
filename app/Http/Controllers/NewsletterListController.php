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
        //
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
    public function destroy(NewsletterList $newsletterList)
    {
        //
    }
}
