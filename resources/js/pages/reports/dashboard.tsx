import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, usePage } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Phone, PhoneCall, PhoneOff, Clock } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Dashboard', href: '/reports/dashboard' },
];

interface Stats {
    total_calls: number;
    answered_calls: number;
    failed_calls: number;
    busy_calls: number;
    no_answer_calls: number;
    total_talk_time: number;
    average_talk_time: number;
}

interface Report {
    id: number;
    date: string;
    campaign: {
        campaign_name: string;
    };
    agent: {
        name: string;
    } | null;
    total_calls: number;
    answered_calls: number;
}

export default function ReportsDashboard() {
    const { todayStats, weekStats, monthStats, recentReports } = usePage().props as {
        todayStats: Stats;
        weekStats: Stats;
        monthStats: Stats;
        recentReports: Report[];
    };

    const formatDuration = (seconds: number) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}h ${minutes}m ${secs}s`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    };

    const StatCard = ({ title, value, subtitle, icon: Icon, period }: {
        title: string;
        value: string | number;
        subtitle: string;
        icon: any;
        period: string;
    }) => (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                <p className="text-xs text-muted-foreground">{subtitle}</p>
                <p className="text-xs text-blue-600 mt-1">{period}</p>
            </CardContent>
        </Card>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports Dashboard" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">📊 Reports Dashboard</h1>
                    <p className="text-gray-600 mt-1">Overview of call center performance</p>
                </div>

                {/* Today's Stats */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4">Today's Performance</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            title="Total Calls"
                            value={todayStats?.total_calls || 0}
                            subtitle={`${todayStats?.answered_calls || 0} answered`}
                            icon={Phone}
                            period="Today"
                        />
                        <StatCard
                            title="Answer Rate"
                            value={`${todayStats?.total_calls > 0 ? ((todayStats.answered_calls / todayStats.total_calls) * 100).toFixed(1) : 0}%`}
                            subtitle={`${todayStats?.answered_calls || 0} of ${todayStats?.total_calls || 0}`}
                            icon={PhoneCall}
                            period="Today"
                        />
                        <StatCard
                            title="Failed Calls"
                            value={todayStats?.failed_calls || 0}
                            subtitle={`${todayStats?.busy_calls || 0} busy, ${todayStats?.no_answer_calls || 0} no answer`}
                            icon={PhoneOff}
                            period="Today"
                        />
                        <StatCard
                            title="Talk Time"
                            value={formatDuration(todayStats?.total_talk_time || 0)}
                            subtitle={`Avg: ${formatDuration(todayStats?.average_talk_time || 0)}`}
                            icon={Clock}
                            period="Today"
                        />
                    </div>
                </div>

                {/* This Week's Stats */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4">This Week's Performance</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            title="Total Calls"
                            value={weekStats?.total_calls || 0}
                            subtitle={`${weekStats?.answered_calls || 0} answered`}
                            icon={Phone}
                            period="This Week"
                        />
                        <StatCard
                            title="Answer Rate"
                            value={`${weekStats?.total_calls > 0 ? ((weekStats.answered_calls / weekStats.total_calls) * 100).toFixed(1) : 0}%`}
                            subtitle={`${weekStats?.answered_calls || 0} of ${weekStats?.total_calls || 0}`}
                            icon={PhoneCall}
                            period="This Week"
                        />
                        <StatCard
                            title="Failed Calls"
                            value={weekStats?.failed_calls || 0}
                            subtitle={`${weekStats?.busy_calls || 0} busy, ${weekStats?.no_answer_calls || 0} no answer`}
                            icon={PhoneOff}
                            period="This Week"
                        />
                        <StatCard
                            title="Talk Time"
                            value={formatDuration(weekStats?.total_talk_time || 0)}
                            subtitle={`Avg: ${formatDuration(weekStats?.average_talk_time || 0)}`}
                            icon={Clock}
                            period="This Week"
                        />
                    </div>
                </div>

                {/* This Month's Stats */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4">This Month's Performance</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            title="Total Calls"
                            value={monthStats?.total_calls || 0}
                            subtitle={`${monthStats?.answered_calls || 0} answered`}
                            icon={Phone}
                            period="This Month"
                        />
                        <StatCard
                            title="Answer Rate"
                            value={`${monthStats?.total_calls > 0 ? ((monthStats.answered_calls / monthStats.total_calls) * 100).toFixed(1) : 0}%`}
                            subtitle={`${monthStats?.answered_calls || 0} of ${monthStats?.total_calls || 0}`}
                            icon={PhoneCall}
                            period="This Month"
                        />
                        <StatCard
                            title="Failed Calls"
                            value={monthStats?.failed_calls || 0}
                            subtitle={`${monthStats?.busy_calls || 0} busy, ${monthStats?.no_answer_calls || 0} no answer`}
                            icon={PhoneOff}
                            period="This Month"
                        />
                        <StatCard
                            title="Talk Time"
                            value={formatDuration(monthStats?.total_talk_time || 0)}
                            subtitle={`Avg: ${formatDuration(monthStats?.average_talk_time || 0)}`}
                            icon={Clock}
                            period="This Month"
                        />
                    </div>
                </div>

                {/* Recent Reports */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">Recent Reports</h2>
                    <div className="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Campaign
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Agent
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total Calls
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Answered
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Answer Rate
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {recentReports.map((report) => (
                                    <tr key={report.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {new Date(report.date).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {report.campaign.campaign_name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {report.agent?.name || 'All Agents'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {report.total_calls}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {report.answered_calls}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {report.total_calls > 0 
                                                ? ((report.answered_calls / report.total_calls) * 100).toFixed(1)
                                                : 0}%
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        
                        {recentReports.length === 0 && (
                            <div className="text-center py-8 text-gray-500">
                                No reports available yet.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}