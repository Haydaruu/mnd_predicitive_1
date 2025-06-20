<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Imports\AkulakuImport;
use App\Jobs\ProcessAkulakuImport;
use App\Models\Campaign;
use App\Models\Nasbah;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::withCount('nasbahs')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('campaign/index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function show(Campaign $campaign)
    {
        $stats = [
            'total_numbers' => $campaign->nasbahs()->count(),
            'called_numbers' => $campaign->nasbahs()->where('is_called', true)->count(),
            'remaining_numbers' => $campaign->nasbahs()->where('is_called', false)->count(),
            'total_calls' => $campaign->calls()->count(),
            'answered_calls' => $campaign->calls()->where('status', 'answered')->count(),
            'failed_calls' => $campaign->calls()->where('status', 'failed')->count(),
            'busy_calls' => $campaign->calls()->where('status', 'busy')->count(),
            'no_answer_calls' => $campaign->calls()->where('status', 'no_answer')->count(),
        ];

        return Inertia::render('campaign/show', [
            'campaign' => $campaign,
            'stats' => $stats,
        ]);
    }

    public function showUploadForm()
    {
        return Inertia::render('campaign/upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'campaign_name' => 'required|string',
            'product_type' => 'required|string',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        $path = $request->file('file')->store('campaign_files');

        $campaign = Campaign::create([
            'campaign_name' => $request->campaign_name,
            'product_type' => $request->product_type,
            'dialing_type' => 'predictive',
            'created_by' => auth()->user()->name,
            'file_path' => $path,
            'status' => 'pending',
            'is_active' => false,
        ]);

        Log::info('✅ Campaign created', ['id' => $campaign->id, 'path' => $path]);

        // Process the file based on product type
        switch ($request->product_type) {
            case 'akulaku':  
                ProcessAkulakuImport::dispatch($path, $campaign->id);
                break;
            default:
                // For other product types, use the same import logic for now
                ProcessAkulakuImport::dispatch($path, $campaign->id);
                break;
        }

        return redirect()->route('campaign')->with('success', 'Campaign uploaded successfully and is being processed.');
    }
}