<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Agent;
use App\Models\Nasbah;
use App\Models\Call;
use App\Models\CallerId;
use App\Events\CallRouted;
use App\Services\AsteriskAMIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class PredictiveDialerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $campaign;
    protected $amiService;
    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Don't retry failed jobs
    public $maxExceptions = 3;

    // Predictive algorithm variables
    private $callsInProgress = [];
    private $agentStats = [];
    private $campaignStats = [
        'total_calls' => 0,
        'answered_calls' => 0,
        'abandoned_calls' => 0,
        'answer_rate' => 0,
        'abandon_rate' => 0,
    ];

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle(): void
    {
        Log::info('🎯 Predictive Dialer started', [
            'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->campaign_name
        ]);

        try {
            // Check if campaign is still active
            $campaign = $this->campaign->fresh();
            if (!$campaign || !$campaign->is_active) {
                Log::info('⏹️ Campaign is no longer active, stopping dialer', [
                    'campaign_id' => $this->campaign->id
                ]);
                return;
            }

            $this->amiService = new AsteriskAMIService();
            
            if (!$this->amiService->connect()) {
                Log::error('❌ Failed to connect to Asterisk AMI');
                $this->campaign->update([
                    'status' => 'stopped', 
                    'is_active' => false,
                    'stopped_at' => now()
                ]);
                return;
            }

            $this->initializeCampaignStats();
            $this->runPredictiveDialingLoop();

        } catch (Exception $e) {
            Log::error('❌ Predictive Dialer Job failed', [
                'campaign_id' => $this->campaign->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->campaign->update([
                'status' => 'stopped', 
                'is_active' => false,
                'stopped_at' => now()
            ]);
        } finally {
            if ($this->amiService) {
                $this->amiService->disconnect();
            }
        }

        Log::info('🛑 Predictive Dialer stopped', [
            'campaign_id' => $this->campaign->id,
            'final_stats' => $this->campaignStats
        ]);
    }

    private function initializeCampaignStats(): void
    {
        $this->campaignStats = [
            'total_calls' => $this->campaign->calls()->count(),
            'answered_calls' => $this->campaign->calls()->where('status', 'answered')->count(),
            'abandoned_calls' => $this->campaign->calls()->where('disposition', 'abandoned')->count(),
            'answer_rate' => 0,
            'abandon_rate' => 0,
        ];

        if ($this->campaignStats['total_calls'] > 0) {
            $this->campaignStats['answer_rate'] = ($this->campaignStats['answered_calls'] / $this->campaignStats['total_calls']) * 100;
            $this->campaignStats['abandon_rate'] = ($this->campaignStats['abandoned_calls'] / $this->campaignStats['total_calls']) * 100;
        }

        Log::info('📊 Campaign stats initialized', [
            'campaign_id' => $this->campaign->id,
            'stats' => $this->campaignStats
        ]);
    }

    private function runPredictiveDialingLoop(): void
    {
        $maxIterations = 1000;
        $iteration = 0;
        $lastStatsUpdate = time();

        while ($iteration < $maxIterations) {
            $iteration++;
            
            // Check if campaign is still active
            $campaign = $this->campaign->fresh();
            if (!$campaign || !$campaign->is_active) {
                Log::info('⏹️ Campaign stopped, exiting dialing loop', [
                    'campaign_id' => $this->campaign->id,
                    'iteration' => $iteration
                ]);
                break;
            }
            
            try {
                // Update stats every 30 seconds
                if (time() - $lastStatsUpdate >= 30) {
                    $this->updateCampaignStats();
                    $lastStatsUpdate = time();
                }

                // Clean up completed calls
                $this->cleanupCompletedCalls();

                // Process predictive dialing
                $this->processPredictiveDialing();

                // Monitor active calls
                $this->monitorActiveCalls();

                sleep(2); // Wait 2 seconds before next iteration

            } catch (Exception $e) {
                Log::error('❌ Error in dialing loop', [
                    'campaign_id' => $this->campaign->id,
                    'iteration' => $iteration,
                    'message' => $e->getMessage()
                ]);
                sleep(5); // Wait longer on error
            }
        }
    }

    private function processPredictiveDialing(): void
    {
        // Get available agents
        $availableAgents = Agent::where('status', 'idle')->get();
        
        if ($availableAgents->isEmpty()) {
            Log::debug('⏳ No available agents', ['campaign_id' => $this->campaign->id]);
            return;
        }

        // Calculate how many calls to make based on predictive algorithm
        $callsToMake = $this->calculateCallsToMake($availableAgents->count());

        if ($callsToMake <= 0) {
            Log::debug('📊 Predictive algorithm says no calls needed', [
                'campaign_id' => $this->campaign->id,
                'available_agents' => $availableAgents->count(),
                'calls_in_progress' => count($this->callsInProgress)
            ]);
            return;
        }

        // Get uncalled numbers
        $uncalledNasabah = Nasbah::where('campaign_id', $this->campaign->id)
            ->where('is_called', false)
            ->limit($callsToMake)
            ->get();

        if ($uncalledNasabah->isEmpty()) {
            Log::info('📞 No more numbers to call', ['campaign_id' => $this->campaign->id]);
            $this->campaign->update([
                'status' => 'completed', 
                'is_active' => false,
                'stopped_at' => now()
            ]);
            return;
        }

        Log::info('🔄 Processing dialing batch', [
            'campaign_id' => $this->campaign->id,
            'available_agents' => $availableAgents->count(),
            'calls_to_make' => $callsToMake,
            'uncalled_numbers' => $uncalledNasabah->count()
        ]);

        foreach ($uncalledNasabah as $nasabah) {
            if ($callsToMake <= 0) break;

            $this->initiateCall($nasabah);
            $callsToMake--;
        }
    }

    private function calculateCallsToMake(int $availableAgents): int
    {
        $maxConcurrent = config('asterisk.dialer.max_concurrent_calls', 10);
        $predictiveRatio = config('asterisk.dialer.predictive_ratio', 2.5);
        $abandonThreshold = config('asterisk.dialer.abandon_rate_threshold', 5);

        $currentCalls = count($this->callsInProgress);
        
        // Don't exceed max concurrent calls
        if ($currentCalls >= $maxConcurrent) {
            return 0;
        }

        // If abandon rate is too high, be more conservative
        if ($this->campaignStats['abandon_rate'] > $abandonThreshold) {
            $predictiveRatio = max(1.0, $predictiveRatio * 0.8);
            Log::warning('⚠️ High abandon rate detected, reducing predictive ratio', [
                'campaign_id' => $this->campaign->id,
                'abandon_rate' => $this->campaignStats['abandon_rate'],
                'new_ratio' => $predictiveRatio
            ]);
        }

        // Calculate optimal calls based on available agents and predictive ratio
        $optimalCalls = (int) ceil($availableAgents * $predictiveRatio);
        
        // Don't make more calls than we can handle
        $callsToMake = min(
            $optimalCalls - $currentCalls,
            $maxConcurrent - $currentCalls,
            $availableAgents * 3 // Safety limit
        );

        return max(0, $callsToMake);
    }

    private function initiateCall(Nasbah $nasabah): void
    {
        $callerId = CallerId::where('is_active', true)->inRandomOrder()->first();

        if (!$callerId) {
            Log::error('❌ No active caller ID available');
            return;
        }

        try {
            $call = Call::create([
                'campaign_id' => $this->campaign->id,
                'nasbah_id' => $nasabah->id,
                'agent_id' => null, // Will be assigned when answered
                'caller_id' => $callerId->id,
                'status' => 'ringing',
                'call_started_at' => now(),
            ]);

            // Mark number as called
            $nasabah->update(['is_called' => true]);

            // Initiate call through Asterisk AMI
            if ($this->makeAsteriskCall($call, $nasabah, $callerId)) {
                // Add to calls in progress
                $this->callsInProgress[$call->id] = [
                    'call' => $call,
                    'nasabah' => $nasabah,
                    'started_at' => time(),
                    'status' => 'ringing'
                ];

                Log::info('📞 Call initiated', [
                    'call_id' => $call->id,
                    'customer_phone' => $nasabah->phone,
                    'campaign_id' => $this->campaign->id
                ]);
            } else {
                // Failed to initiate call
                $this->handleCallFailure($call);
                $nasabah->update(['is_called' => false]); // Allow retry
            }

        } catch (Exception $e) {
            Log::error('❌ Failed to initiate call', [
                'nasabah_id' => $nasabah->id,
                'message' => $e->getMessage()
            ]);
            
            // Cleanup on failure
            $nasabah->update(['is_called' => false]);
        }
    }

    private function makeAsteriskCall(Call $call, Nasbah $nasabah, CallerId $callerId): bool
    {
        try {
            // Prepare call variables
            $variables = [
                'CALL_ID' => $call->id,
                'CAMPAIGN_ID' => $this->campaign->id,
                'CUSTOMER_NAME' => $nasabah->name,
                'CUSTOMER_PHONE' => $nasabah->phone,
                'CALLERID_NUM' => $callerId->number,
                'PREDICTIVE_CALL' => '1',
            ];

            // Customer channel (outbound call)
            $customerChannel = config('asterisk.channels.trunk_prefix') . $nasabah->phone;
            $context = config('asterisk.contexts.predictive');
            $extension = 's'; // Start extension
            $timeout = config('asterisk.dialer.answer_timeout', 30) * 1000; // Convert to milliseconds

            // Originate call to customer
            $success = $this->amiService->originateCall(
                $customerChannel,
                $context,
                $extension,
                '1',
                $variables,
                $timeout
            );

            if ($success) {
                Log::info('📞 Asterisk call initiated successfully', [
                    'call_id' => $call->id,
                    'customer_phone' => $nasabah->phone,
                    'channel' => $customerChannel
                ]);
                return true;
            } else {
                Log::error('❌ Failed to initiate Asterisk call', [
                    'call_id' => $call->id,
                    'channel' => $customerChannel
                ]);
                return false;
            }

        } catch (Exception $e) {
            Log::error('❌ Asterisk call error', [
                'call_id' => $call->id,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function monitorActiveCalls(): void
    {
        $currentTime = time();
        $answerTimeout = config('asterisk.dialer.answer_timeout', 30);

        foreach ($this->callsInProgress as $callId => $callData) {
            $call = $callData['call'];
            $elapsedTime = $currentTime - $callData['started_at'];

            // Check if call has timed out
            if ($elapsedTime > $answerTimeout && $callData['status'] === 'ringing') {
                Log::info('⏰ Call timed out', [
                    'call_id' => $callId,
                    'elapsed_time' => $elapsedTime
                ]);

                $this->handleCallTimeout($call);
                unset($this->callsInProgress[$callId]);
                continue;
            }

            // Check call status via AMI (simplified for now)
            // In a real implementation, you'd use AMI events or status checks
            $this->checkCallStatus($call);
        }
    }

    private function checkCallStatus(Call $call): void
    {
        // This is a simplified version
        // In production, you'd use AMI events or channel status checks
        $call = $call->fresh();
        
        if ($call && in_array($call->status, ['answered', 'failed', 'busy', 'no_answer'])) {
            // Call has been updated, remove from progress
            unset($this->callsInProgress[$call->id]);
            
            if ($call->status === 'answered' && !$call->agent_id) {
                // Find available agent and assign
                $this->assignAgentToCall($call);
            }
        }
    }

    private function assignAgentToCall(Call $call): void
    {
        $availableAgent = Agent::where('status', 'idle')->first();
        
        if ($availableAgent) {
            $call->update(['agent_id' => $availableAgent->id]);
            $availableAgent->update(['status' => 'busy']);
            
            // Broadcast to agent
            $nasabah = $call->nasbah;
            event(new CallRouted($availableAgent, $nasabah));
            
            Log::info('👤 Agent assigned to call', [
                'call_id' => $call->id,
                'agent_id' => $availableAgent->id,
                'agent_name' => $availableAgent->name
            ]);
        } else {
            // No available agent - this is an abandoned call
            $call->update([
                'disposition' => 'abandoned',
                'call_ended_at' => now()
            ]);
            
            Log::warning('⚠️ Call abandoned - no available agent', [
                'call_id' => $call->id
            ]);
        }
    }

    private function handleCallTimeout(Call $call): void
    {
        $call->update([
            'status' => 'no_answer',
            'disposition' => 'no_answer',
            'call_ended_at' => now(),
            'duration' => 0,
        ]);

        Log::info('📞 Call timed out', ['call_id' => $call->id]);
    }

    private function handleCallFailure(Call $call): void
    {
        $call->update([
            'status' => 'failed',
            'disposition' => 'failed',
            'call_ended_at' => now(),
            'duration' => 0,
        ]);

        Log::info('📞 Call failed', ['call_id' => $call->id]);
    }

    private function cleanupCompletedCalls(): void
    {
        foreach ($this->callsInProgress as $callId => $callData) {
            $call = $callData['call']->fresh();
            
            if (!$call || in_array($call->status, ['answered', 'failed', 'busy', 'no_answer'])) {
                unset($this->callsInProgress[$callId]);
            }
        }
    }

    private function updateCampaignStats(): void
    {
        $this->campaignStats = [
            'total_calls' => $this->campaign->calls()->count(),
            'answered_calls' => $this->campaign->calls()->where('status', 'answered')->count(),
            'abandoned_calls' => $this->campaign->calls()->where('disposition', 'abandoned')->count(),
            'answer_rate' => 0,
            'abandon_rate' => 0,
        ];

        if ($this->campaignStats['total_calls'] > 0) {
            $this->campaignStats['answer_rate'] = ($this->campaignStats['answered_calls'] / $this->campaignStats['total_calls']) * 100;
            $this->campaignStats['abandon_rate'] = ($this->campaignStats['abandoned_calls'] / $this->campaignStats['total_calls']) * 100;
        }

        Log::debug('📊 Campaign stats updated', [
            'campaign_id' => $this->campaign->id,
            'stats' => $this->campaignStats
        ]);
    }

    public function failed(Exception $exception): void
    {
        Log::error('❌ PredictiveDialerJob failed', [
            'campaign_id' => $this->campaign->id,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Update campaign status on job failure
        $this->campaign->update([
            'status' => 'stopped',
            'is_active' => false,
            'stopped_at' => now(),
        ]);
    }
}