<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Jobs\PredictiveDialerJob;
use App\Events\CampaignStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PredictiveDialerController extends Controller
{
    public function start(Campaign $campaign): JsonResponse
    {
        try {
            // Validate user permissions
            if (!auth()->check()) {
                Log::warning('Unauthorized access attempt to start campaign', [
                    'campaign_id' => $campaign->id,
                    'ip' => request()->ip()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Check user role permissions
            $user = auth()->user();
            if (!in_array($user->role, ['SuperAdmin', 'Admin'])) {
                Log::warning('Insufficient permissions to start campaign', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'campaign_id' => $campaign->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

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

            Log::info("🚀 Predictive dialer started for campaign: {$campaign->campaign_name}", [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'remaining_numbers' => $remainingNumbers
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Predictive dialer started successfully',
                'data' => [
                    'campaign' => $campaign->fresh(),
                    'stats' => [
                        'total_numbers' => $totalNumbers,
                        'remaining_numbers' => $remainingNumbers,
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Failed to start campaign {$campaign->id}: " . $e->getMessage(), [
                'exception' => $e,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stop(Campaign $campaign): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $user = auth()->user();
            if (!in_array($user->role, ['SuperAdmin', 'Admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $campaign->update([
                'status' => 'stopped',
                'is_active' => false,
                'stopped_at' => now(),
            ]);

            // Broadcast status change
            event(new CampaignStatusChanged($campaign));

            Log::info("🛑 Predictive dialer stopped for campaign: {$campaign->campaign_name}", [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Predictive dialer stopped successfully',
                'data' => [
                    'campaign' => $campaign->fresh(),
                ],
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
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $user = auth()->user();
            if (!in_array($user->role, ['SuperAdmin', 'Admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

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
                'data' => [
                    'campaign' => $campaign->fresh(),
                ],
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
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $user = auth()->user();
            if (!in_array($user->role, ['SuperAdmin', 'Admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

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
                'data' => [
                    'campaign' => $campaign->fresh(),
                ],
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
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

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
                'data' => [
                    'campaign' => $campaign,
                    'stats' => $stats,
                ],
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