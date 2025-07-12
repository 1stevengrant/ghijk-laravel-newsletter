<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Data\ImportData;
use Illuminate\Http\Request;
use App\Events\ImportStarted;
use App\Jobs\ProcessImportJob;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240',
            'import_type' => 'required|in:existing,new',
            'newsletter_list_id' => 'required_if:import_type,existing|exists:newsletter_lists,id',
            'new_list_name' => 'required_if:import_type,new|string|max:255',
            'new_list_description' => 'nullable|string',
            'new_list_from_name' => 'required_if:import_type,new|string|max:255',
            'new_list_from_email' => 'required_if:import_type,new|email|max:255',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('imports', $filename, 'local');

        // Verify file was stored
        if (! Storage::disk('local')->exists('imports/' . $filename)) {
            throw new \Exception('Failed to store uploaded file');
        }

        $import = Import::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'pending',
            'newsletter_list_id' => $request->import_type === 'existing' ? $request->newsletter_list_id : null,
            'new_list_data' => $request->import_type === 'new' ? [
                'name' => $request->new_list_name,
                'description' => $request->new_list_description,
                'from_name' => $request->new_list_from_name,
                'from_email' => $request->new_list_from_email,
            ] : null,
        ]);

        // Small delay to ensure file is fully written
        usleep(100000); // 100ms delay

        ProcessImportJob::dispatch($import);

        // Dispatch started event immediately so frontend gets notification
        ImportStarted::dispatch($import);

        return response()->json([
            'message' => 'Import started successfully',
            'import' => ImportData::fromModel($import),
        ]);
    }

    public function show(Import $import)
    {
        $import->load('newsletterList');

        return response()->json([
            'import' => ImportData::fromModel($import),
        ]);
    }
}
