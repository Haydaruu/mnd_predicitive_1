<?php

namespace App\Jobs;

use App\Imports\AkulakuImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Events\CampaignImportFinished;
use App\Models\Campaign;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessAkulakuImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filePath;
    public $campaignId;
    public $timeout = 600; // 10 minutes timeout
    public $tries = 1; // Only 1 attempt to avoid repeated failures
    public $maxExceptions = 3;

    public function __construct($filePath, $campaignId)
    {
        $this->filePath = $filePath;
        $this->campaignId = $campaignId;   
    }

    public function handle(): void
    {
        try {
            Log::info('🎯 Starting import process', [
                'campaign_id' => $this->campaignId,
                'file_path' => $this->filePath,
            ]);

            // Check if campaign exists
            $campaign = Campaign::find($this->campaignId);
            if (!$campaign) {
                Log::error('❌ Campaign not found', ['campaign_id' => $this->campaignId]);
                throw new Exception("Campaign with ID {$this->campaignId} not found");
            }

            // Check if file exists
            if (!Storage::exists($this->filePath)) {
                Log::error('❌ File not found', ['file_path' => $this->filePath]);
                throw new Exception("File not found: {$this->filePath}");
            }

            $fullPath = Storage::path($this->filePath);
            Log::info('📂 Full file path resolved', ['path' => $fullPath]);

            // Verify file is readable
            if (!is_readable($fullPath)) {
                Log::error('❌ File is not readable', ['path' => $fullPath]);
                throw new Exception("File is not readable: {$fullPath}");
            }

            // Check file size
            $fileSize = filesize($fullPath);
            Log::info('📊 File size', ['size' => $fileSize . ' bytes']);

            if ($fileSize === 0) {
                Log::error('❌ File is empty', ['path' => $fullPath]);
                throw new Exception("File is empty: {$fullPath}");
            }

            // Test if file can be opened by Excel reader
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fullPath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($fullPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $highestRow = $worksheet->getHighestRow();
                
                Log::info('📋 Excel file validation', [
                    'highest_row' => $highestRow,
                    'file_type' => get_class($reader)
                ]);
                
                if ($highestRow <= 1) {
                    throw new Exception("Excel file appears to be empty (only header row found)");
                }
                
            } catch (\Exception $e) {
                Log::error('❌ Excel file validation failed', [
                    'error' => $e->getMessage(),
                    'file_path' => $fullPath
                ]);
                throw new Exception("Invalid Excel file: " . $e->getMessage());
            }
            // Update campaign status to processing
            $campaign->update(['status' => 'processing']);

            // Process the import
            Log::info('🔄 Starting Excel import');
            Excel::import(new AkulakuImport($this->campaignId), $fullPath);

            // Verify import results
            $importedCount = $campaign->nasbahs()->count();
            Log::info('📊 Import verification', [
                'campaign_id' => $this->campaignId,
                'imported_count' => $importedCount
            ]);

            if ($importedCount === 0) {
                throw new Exception("No data was imported from the Excel file");
            }
            // Update campaign status to pending (ready to start)
            $campaign->update(['status' => 'pending']);

            // Broadcast import finished event
            event(new CampaignImportFinished($this->campaignId));

            Log::info('✅ Import completed successfully', [
                'campaign_id' => $this->campaignId,
                'nasbahs_count' => $importedCount
            ]);

        } catch (Exception $e) {
            Log::error('❌ Import failed', [
                'campaign_id' => $this->campaignId,
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update campaign status to failed
            if ($campaign = Campaign::find($this->campaignId)) {
                $campaign->update([
                    'status' => 'failed',
                    'keterangan' => 'Import failed: ' . $e->getMessage()
                ]);
            }

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('❌ Import job failed permanently', [
            'campaign_id' => $this->campaignId,
            'file_path' => $this->filePath,
            'error' => $exception->getMessage(),
        ]);

        // Update campaign status to failed
        if ($campaign = Campaign::find($this->campaignId)) {
            $campaign->update([
                'status' => 'failed',
                'keterangan' => 'Import failed: ' . $exception->getMessage()
            ]);
        }

        // Clean up the uploaded file
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
            Log::info('🗑️ Cleaned up uploaded file', ['file_path' => $this->filePath]);
        }
    }
}