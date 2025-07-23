import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, usePage } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Download, Phone, PhoneCall, PhoneOff, Clock } from 'lucide-react';

interface Campaign {
    id: number;
    campaign_name: string;
    product_type: string;
    created_by: string;
    status: string;
}

interface Stats {
    total_numbers: number;
    called_numbers: number;
    remaining_numbers: number;
    total_calls: number;
    answered_calls: number;
    failed_calls: number;
    busy_calls: number;
    no_answer_calls: number;
}

interface DailyStat {
    date: string;
    total_calls: number;
    answered_calls: number;
    failed_calls: number;
    busy_calls: number;
    no_answer_calls: number;
}

interface AgentPerformance {
    agent_id: number;
    agent: {
        name: string;
    };
    total_calls: number;
    answered_calls: number;
    failed_calls: number;
    total_talk_time: number;
    avg_talk_time: number;
}

interface RecentCall {
    id: number;
    agent: {
        name: string;
    } | null;
    nasbah: {
        name: string;
        phone: string;
    };
    callerId: {
        number: string;
    } | null;
    status: string;
    disposition: string;
    call_started_at: string;
    call_ended_at: string;
    duration: number;
}

export default function CampaignReport() {
    const { campaign, stats, dailyStats, agentPerformance, recentCalls } = usePage().props as {
        campaign: Campaign;
        stats: Stats;
        dailyStats: DailyStat[];
        agentPerformance: AgentPerformance[];
        recentCalls: RecentCall[];
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reports', href: '/reports' },
        { title: 'Campaign Reports', href: '/reports/call-reports' },
        { title: campaign.campaign_name, href: `/reports/campaign/${campaign.id}` },
    ];

    const exportCampaignReport = () => {
        window.open(`/reports/export/campaign/${campaign.id}`, '_blank');
    };

    const formatDuration = (seconds: number) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    };

    const answerRate = stats.total_calls > 0 ? (stats.answered_calls / stats.total_calls) * 100 : 0;
    const completionRate = stats.total_numbers > 0 ? (stats.called_numbers / stats.total_numbers) * 100 : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Campaign Report: ${campaign.campaign_name}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">{campaign.campaign_name}</h1>
                        <p className="text-gray-600 mt-1">Campaign Report - {campaign.product_type}</p>
                    </div>
                    <Button onClick={exportCampaignReport}>
                        <Download className="h-4 w-4 mr-2" />
                        Export Excel
                    </Button>
                </div>

                {/* Summary Stats */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Numbers</CardTitle>
                            <Phone className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_numbers.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                {completionRate.toFixed(1)}% completed
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Calls</CardTitle>
                            <PhoneCall className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_calls.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.answered_calls} answered
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Answer Rate</CardTitle>
                            <PhoneCall className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{answerRate.toFixed(1)}%</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.answered_calls} of {stats.total_calls}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Failed Calls</CardTitle>
                            <PhoneOff className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.failed_calls + stats.busy_calls + stats.no_answer_calls}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.busy_calls} busy, {stats.no_answer_calls} no answer
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Daily Statistics */}
                <div className="grid gap-6 md:grid-cols-2 mb-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Daily Call Volume (Last 30 Days)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-64 overflow-y-auto">
                                {dailyStats.map((day) => (
                                    <div key={day.date} className="flex justify-between items-center py-2 border-b">
                                        <span className="text-sm text-gray-600">
                                            {new Date(day.date).toLocaleDateString()}
                                        </span>
                                        <div className="flex gap-4 text-sm">
                                            <span className="text-blue-600">{day.total_calls} total</span>
                                            <span className="text-green-600">{day.answered_calls} answered</span>
                                            <span className="text-red-600">{day.failed_calls + day.busy_calls + day.no_answer_calls} failed</span>
                                        </div>
                                    </div>
                                ))}
                                {dailyStats.length === 0 && (
                                    <p className="text-center text-gray-500 py-4">No data available</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Agent Performance */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Agent Performance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 max-h-64 overflow-y-auto">
                                {agentPerformance.map((agent) => {
                                    const agentAnswerRate = agent.total_calls > 0 
                                        ? (agent.answered_calls / agent.total_calls) * 100 
                                        : 0;

                                    return (
                                        <div key={agent.agent_id} className="flex justify-between items-center py-2 border-b">
                                            <div>
                                                <span className="font-medium">{agent.agent.name}</span>
                                                <p className="text-xs text-gray-500">
                                                    {agent.total_calls} calls • {agentAnswerRate.toFixed(1)}% answer rate
                                                </p>
                                            </div>
                                            <div className="text-right text-sm">
                                                <div className="text-green-600">{agent.answered_calls} answered</div>
                                                <div className="text-gray-500">{formatDuration(agent.total_talk_time)} talk time</div>
                                            </div>
                                        </div>
                                    );
                                })}
                                {agentPerformance.length === 0 && (
                                    <p className="text-center text-gray-500 py-4">No agent data available</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Calls */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Calls (Last 50)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Agent
                                        </th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Customer
                                        </th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Phone
                                        </th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Status
                                        </th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Duration
                                        </th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Time
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {recentCalls.map((call) => (
                                        <tr key={call.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-2 text-sm text-gray-900">
                                                {call.agent?.name || 'N/A'}
                                            </td>
                                            <td className="px-4 py-2 text-sm text-gray-900">
                                                {call.nasbah.name}
                                            </td>
                                            <td className="px-4 py-2 text-sm text-gray-900">
                                                {call.nasbah.phone}
                                            </td>
                                            <td className="px-4 py-2 text-sm">
                                                <span className={`px-2 py-1 text-xs rounded-full ${
                                                    call.status === 'answered' 
                                                        ? 'bg-green-100 text-green-800'
                                                        : call.status === 'busy'
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {call.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2 text-sm text-gray-900">
                                                {formatDuration(call.duration || 0)}
                                            </td>
                                            <td className="px-4 py-2 text-sm text-gray-500">
                                                {new Date(call.call_started_at).toLocaleString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            
                            {recentCalls.length === 0 && (
                                <div className="text-center py-8 text-gray-500">
                                    No calls found for this campaign.
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}