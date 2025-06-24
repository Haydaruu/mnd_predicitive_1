<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Campaign;
use App\Models\Call;
use App\Models\CallReport;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // User statistics
        $userStats = [
            'total_users' => User::count(),
            'super_admins' => User::where('role', UserRole::SuperAdmin)->count(),
            'admins' => User::where('role', UserRole::Admin)->count(),
            'agents' => User::where('role', UserRole::Agent)->count(),
        ];

        // Campaign statistics by product type
        $campaignStats = Campaign::select('product_type', DB::raw('count(*) as total'))
            ->groupBy('product_type')
            ->get()
            ->keyBy('product_type');

        // Recent campaigns
        $recentCampaigns = Campaign::with(['nasbahs' => function($query) {
                $query->selectRaw('campaign_id, count(*) as total_numbers, sum(case when is_called = 1 then 1 else 0 end) as called_numbers');
                $query->groupBy('campaign_id');
            }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Call statistics for today
        $todayStats = [
            'total_calls' => Call::whereDate('call_started_at', Carbon::today())->count(),
            'answered_calls' => Call::whereDate('call_started_at', Carbon::today())->where('status', 'answered')->count(),
            'failed_calls' => Call::whereDate('call_started_at', Carbon::today())->whereIn('status', ['failed', 'busy', 'no_answer'])->count(),
        ];

        // Weekly performance
        $weeklyPerformance = Call::select(
                DB::raw('DATE(call_started_at) as date'),
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN status = "answered" THEN 1 ELSE 0 END) as answered_calls')
            )
            ->where('call_started_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top performing agents
        $topAgents = CallReport::select('agent_id', DB::raw('SUM(answered_calls) as total_answered'))
            ->with('agent')
            ->where('date', '>=', Carbon::now()->subDays(30))
            ->groupBy('agent_id')
            ->orderBy('total_answered', 'desc')
            ->limit(5)
            ->get();

        // Campaign status distribution
        $campaignStatusStats = Campaign::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return Inertia::render('admin/AdminDashboard', [
            'userStats' => $userStats,
            'campaignStats' => $campaignStats,
            'recentCampaigns' => $recentCampaigns,
            'todayStats' => $todayStats,
            'weeklyPerformance' => $weeklyPerformance,
            'topAgents' => $topAgents,
            'campaignStatusStats' => $campaignStatusStats,
        ]);
    }
}