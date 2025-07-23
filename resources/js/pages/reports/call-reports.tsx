import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { useState } from 'react';
import { Download, Filter, RefreshCw } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Call Reports', href: '/reports/call-reports' },
];

interface CallReport {
    id: number;
    date: string;
    campaign: {
        id: number;
        campaign_name: string;
    };
    agent: {
        id: number;
        name: string;
    } | null;
    total_calls: number;
    answered_calls: number;
    failed_calls: number;
    busy_calls: number;
    no_answer_calls: number;
    total_talk_time: number;
    average_talk_time: number;
}

interface Campaign {
    id: number;
    campaign_name: string;
}

interface Agent {
    id: number;
    name: string;
}

export default function CallReports() {
    const { reports, campaigns, agents, filters } = usePage().props as {
        reports: {
            data: CallReport[];
            links: any[];
        };
        campaigns: Campaign[];
        agents: Agent[];
        filters: {
            campaign_id?: string;
            agent_id?: string;
            date_from?: string;
            date_to?: string;
        };
    };

    const [localFilters, setLocalFilters] = useState(filters);

    const handleFilterChange = (key: string, value: string) => {
        setLocalFilters(prev => ({
            ...prev,
            [key]: value
        }));
    };

    const applyFilters = () => {
        router.get('/reports/call-reports', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get('/reports/call-reports', {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const exportReports = () => {
        const params = new URLSearchParams(localFilters as any).toString();
        window.open(`/reports/export/calls?${params}`, '_blank');
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Call Reports" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">📊 Call Reports</h1>
                    <div className="flex gap-2">
                        <Button onClick={exportReports} variant="outline">
                            <Download className="h-4 w-4 mr-2" />
                            Export Excel
                        </Button>
                        <Link href="/reports/dashboard">
                            <Button variant="outline">
                                Dashboard
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white rounded-lg shadow border border-gray-200 p-6 mb-6">
                    <h3 className="text-lg font-semibold mb-4">Filters</h3>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Campaign</label>
                            <Select 
                                value={localFilters.campaign_id || ''} 
                                onValueChange={(value) => handleFilterChange('campaign_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Campaigns" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Campaigns</SelectItem>
                                    {campaigns.map((campaign) => (
                                        <SelectItem key={campaign.id} value={campaign.id.toString()}>
                                            {campaign.campaign_name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Agent</label>
                            <Select 
                                value={localFilters.agent_id || ''} 
                                onValueChange={(value) => handleFilterChange('agent_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Agents" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Agents</SelectItem>
                                    {agents.map((agent) => (
                                        <SelectItem key={agent.id} value={agent.id.toString()}>
                                            {agent.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <Input
                                type="date"
                                value={localFilters.date_from || ''}
                                onChange={(e) => handleFilterChange('date_from', e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <Input
                                type="date"
                                value={localFilters.date_to || ''}
                                onChange={(e) => handleFilterChange('date_to', e.target.value)}
                            />
                        </div>

                        <div className="flex items-end gap-2">
                            <Button onClick={applyFilters}>
                                <Filter className="h-4 w-4 mr-2" />
                                Apply
                            </Button>
                            <Button onClick={clearFilters} variant="outline">
                                <RefreshCw className="h-4 w-4 mr-2" />
                                Clear
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Reports Table */}
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
                                    Failed
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Answer Rate
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Talk Time
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {reports.data.map((report) => {
                                const answerRate = report.total_calls > 0 
                                    ? ((report.answered_calls / report.total_calls) * 100).toFixed(1)
                                    : '0';

                                return (
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
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">
                                            {report.answered_calls}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                            {report.failed_calls + report.busy_calls + report.no_answer_calls}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {answerRate}%
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {formatDuration(report.total_talk_time)}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>

                    {reports.data.length === 0 && (
                        <div className="text-center py-8 text-gray-500">
                            No reports found for the selected criteria.
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {reports.links && reports.links.length > 3 && (
                    <div className="flex justify-center mt-6">
                        <nav className="flex space-x-1">
                            {reports.links.map((link, index) => (
                                <Link
                                    key={index}
                                    href={link.url || '#'}
                                    className={`px-3 py-2 text-sm rounded-md ${
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 border'
                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </nav>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}