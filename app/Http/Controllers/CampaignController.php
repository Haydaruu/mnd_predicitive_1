<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Imports\AkulakuImport;
use App\Jobs\ProcessAkulakuImport;
use App\Models\Campaign;
use App\Models\Nasbah;
use App\Exports\CallReportExport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public function nasbahs(Campaign $campaign, Request $request)
    {
        $query = $campaign->nasbahs();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $nasbahs = $query->orderBy('created_at', 'desc')->paginate(50);

        return Inertia::render('campaign/nasbahs', [
            'campaign' => $campaign,
            'nasbahs' => $nasbahs,
        ]);
    }

    public function destroyNasbah(Campaign $campaign, Nasbah $nasbah)
    {
        if ($nasbah->campaign_id !== $campaign->id) {
            return back()->withErrors(['error' => 'Customer data not found in this campaign.']);
        }

        $nasbah->delete();

        return back()->with('success', 'Customer data deleted successfully.');
    }

    public function exportNasbahs(Campaign $campaign)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setTitle('Customer Data');
        
        // Headers
        $headers = ['Name', 'Phone', 'Outstanding', 'Penalty', 'Status', 'Added Date'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Data
        $nasbahs = $campaign->nasbahs()->get();
        $row = 2;
        
        foreach ($nasbahs as $nasbah) {
            $sheet->setCellValue('A' . $row, $nasbah->name);
            $sheet->setCellValue('B' . $row, $nasbah->phone);
            $sheet->setCellValue('C' . $row, $nasbah->outstanding);
            $sheet->setCellValue('D' . $row, $nasbah->denda);
            $sheet->setCellValue('E' . $row, $nasbah->is_called ? 'Called' : 'Not Called');
            $sheet->setCellValue('F' . $row, $nasbah->created_at->format('Y-m-d H:i:s'));
            $row++;
        }
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'customer_data_' . Str::slug($campaign->campaign_name) . '_' . date('Y-m-d') . '.xlsx';
        
        $temp = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($temp);
        
        return response()->download($temp, $filename)->deleteFileAfterSend(true);
    }

    public function showUploadForm()
    {
        return Inertia::render('campaign/upload');
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'campaign_name' => 'required|string|max:255',
                'product_type' => 'required|string|max:100',
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ]);

            Log::info('📤 Starting campaign upload', [
                'campaign_name' => $request->campaign_name,
                'product_type' => $request->product_type,
                'file_name' => $request->file('file')->getClientOriginalName(),
                'file_size' => $request->file('file')->getSize(),
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
            ]);

            // Store the uploaded file
            $path = $request->file('file')->store('campaign_files');
            
            if (!$path) {
                Log::error('❌ Failed to store uploaded file');
                return back()->withErrors(['file' => 'Failed to store uploaded file.']);
            }

            // Verify file was stored
            if (!Storage::exists($path)) {
                Log::error('❌ File not found after storage', ['path' => $path]);
                return back()->withErrors(['file' => 'File was not properly stored.']);
            }

            // Get file size and verify it's readable
            $fullPath = Storage::path($path);
            $fileSize = Storage::size($path);
            
            if ($fileSize === 0) {
                Log::error('❌ Uploaded file is empty', ['path' => $path]);
                Storage::delete($path);
                return back()->withErrors(['file' => 'Uploaded file is empty.']);
            }

            if (!is_readable($fullPath)) {
                Log::error('❌ File is not readable', ['path' => $fullPath]);
                Storage::delete($path);
                return back()->withErrors(['file' => 'File is not readable.']);
            }
            // Create campaign record
            $campaign = Campaign::create([
                'campaign_name' => $request->campaign_name,
                'product_type' => $request->product_type,
                'dialing_type' => 'predictive',
                'created_by' => auth()->user()->name,
                'file_path' => $path,
                'status' => 'uploading',
                'is_active' => false,
            ]);

            Log::info('✅ Campaign created', [
                'id' => $campaign->id, 
                'path' => $path,
                'file_size' => $fileSize,
                'full_path' => $fullPath
            ]);

            // Dispatch import job
            ProcessAkulakuImport::dispatch($path, $campaign->id);

            Log::info('🚀 Import job dispatched', [
                'campaign_id' => $campaign->id,
                'job_class' => ProcessAkulakuImport::class
            ]);

            return redirect()->route('campaign')->with('success', 'Campaign uploaded successfully! Processing will start shortly. Please wait for the status to change to "Pending".');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->except(['file'])
            ]);
            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            Log::error('❌ Upload failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['file'])
            ]);
            return back()->withErrors(['error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    public function destroy(Campaign $campaign)
    {
        try {
            DB::transaction(function () use ($campaign) {
                // Delete related calls first
                $campaign->calls()->delete();
                
                // Delete related nasbahs
                $campaign->nasbahs()->delete();
                
                // Delete the campaign file if exists
                if ($campaign->file_path && Storage::exists($campaign->file_path)) {
                    Storage::delete($campaign->file_path);
                }
                
                // Delete the campaign
                $campaign->delete();
            });

            return redirect()->route('campaign')->with('success', 'Campaign deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete campaign: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete campaign. Please try again.']);
        }
    }
}