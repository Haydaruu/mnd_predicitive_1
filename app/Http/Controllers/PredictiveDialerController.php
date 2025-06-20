<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Jobs\PredictiveDialerJob;
use App\Events\CampaignStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PredictiveDialerController extends Controller
{
    public function start(Campaign $campaign): JsonResponse
    {
        try {
            if ($campaign->status === 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign is already running'
                ], 400);
            }

            // Check if campaign has numbers to call
            $totalNumbers = $campaign->nasbahs()->count();
            $remainingNumbers = $campaign->nasbahs()->where('is_called', false)->count();

            if ($totalNumbers === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign has no numbers to call'
                ], 400);
            }

            if ($remainingNumbers === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'All numbers in this campaign have been called'
                ], 400);
            }

            $campaign->update([
                'status' => 'running',
                'is_active' => true,
                'started_at' => now(),
                'stopped_at' => null,
            ]);

            // Dispatch the predictive dialer job
            PredictiveDialerJob::dispatch($campaign);

            // Broadcast status change
            event(new CampaignStatusChanged($campaign));

            Log::info("🚀 Predictive dialer started for campaign: {$campaign->campaign_name}");

            return response()->json([
                'success' => true,
                'message' => 'Predictive dialer started successfully',
                'campaign' => $campaign->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Failed to start campaign {$campaign->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stop(Campaign $campaign): JsonResponse
    {
        try {
            $campaign->update([
                'status' => 'stopped',
                'is_active' => false,
                'stopped_at' => now(),
            ]);

            // Broadcast status change
            event(new CampaignStatusChanged($campaign));

            Log::info("🛑 Predictive dialer stopped for campaign: {$campaign->campaign_name}");

            return response()->json([
                'success' => true,
                'message' => 'Predictive dialer stopped successfully',
                'campaign' => $campaign->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Failed to stop campaign {$campaign->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pause(Campaign $campaign): JsonResponse
    {
        try {
            if ($campaign->status !== 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign is not running'
                ], 400);
            }

            $campaign->update([
                'status' => 'paused',
                'is_active' => false,
            ]);

            // Broadcast status change
            event(new CampaignStatusChanged($campaign));

            Log::info("⏸️ Predictive dialer paused for campaign: {$campaign->campaign_name}");

            return response()->json([
                'success' => true,
                'message' => 'Predictive dialer paused successfully',
                'campaign' => $campaign->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Failed to pause campaign {$campaign->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to pause campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resume(Campaign $campaign): JsonResponse
    {
        try {
            if ($campaign->status !== 'paused') {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign is not paused'
                ], 400);
            }

            $campaign->update([
                'status' => 'running',
                'is_active' => true,
            ]);

            // Restart the predictive dialer job
            PredictiveDialerJob::dispatch($campaign);

            // Broadcast status change
            event(new CampaignStatusChanged($campaign));

            Log::info("▶️ Predictive dialer resumed for campaign: {$campaign->campaign_name}");

            return response()->json([
                'success' => true,
                'message' => 'Predictive dialer resumed successfully',
                'campaign' => $campaign->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Failed to resume campaign {$campaign->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status(Campaign $campaign): JsonResponse
    {
        try {
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

            return response()->json([
                'success' => true,
                'campaign' => $campaign,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Failed to get campaign status {$campaign->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get campaign status: ' . $e->getMessage()
            ], 500);
        }
    }
}