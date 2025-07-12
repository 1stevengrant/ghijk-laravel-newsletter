<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsletterList;

class BulkDeleteListsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'list_ids' => 'required|array|min:1',
            'list_ids.*' => 'required|integer|exists:newsletter_lists,id',
        ]);

        $deletedCount = NewsletterList::whereIn('id', $request->list_ids)->delete();

        return redirect()->route('lists.index')
            ->with('success', "Successfully deleted {$deletedCount} newsletter list(s).");
    }
}
