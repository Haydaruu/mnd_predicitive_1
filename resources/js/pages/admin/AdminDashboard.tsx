import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, Link } from '@inertiajs/react';
import { Users, UserCheck, Shield, Headphones, Phone, PhoneCall, TrendingUp, Calendar } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/AdminDashboard',
    },
];

interface UserStats {
    total_users: number;
    super_admins: number;
    admins: number;
    agents: number;
}

interface CampaignStats {
    [key: string]: {
        product_type: string;
        total: number;
    };
}

interface Campaign {
    id: number;
    campaign_name: string;
    product_type: string;
    status: string;
    created_at: string;
    nasbahs: Array<{
        total_numbers: number;
        called_numbers: number;
    }>;
}

interface TodayStats {
    total_calls: number;
    answered_calls: number;
    failed_calls: number;
}

interface WeeklyPerformance {
    date: string;
    total_calls: number;
    answered_calls: number;
}

interface TopAgent {
    agent_id: number;
    total_answered: number;
    agent: {
        name: string;
    };
}

interface CampaignStatusStats {
    [key: string]: {
        status: string;
        total: number;
    };
}

export default function AdminDashboard() {
    const { 
        userStats, 
        campaignStats, 
        recentCampaigns, 
        todayStats, 
        weeklyPerformance, 
        topAgents, 
        campaignStatusStats 
    } = usePage().props as {
        userStats: UserStats;
        campaignStats: CampaignStats;
        recentCampaigns: Campaign[];
        todayStats: TodayStats;
        weeklyPerformance: WeeklyPerformance[];
        topAgents: TopAgent[];
        campaignStatusStats: CampaignStatusStats;
    };

    const getStatusBadgeColor = (status: string) => {
        switch (status) {
            case 'running':
                return 'bg-green-100 text-green-800';
            case 'paused':
                return 'bg-yellow-100 text-yellow-800';
            case 'completed':
                return 'bg-blue-100 text-blue-800';
            case 'stopped':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">📊 Admin Dashboard</h1>
                    <p className="text-gray-600 mt-1">Overview of system performance and statistics</p>
                </div>

                {/* User Statistics */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4">User Statistics</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                                <Users className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{userStats.total_users}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Super Admins</CardTitle>
                                <Shield className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{userStats.super_admins}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Admins</CardTitle>
                                <UserCheck className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{userStats.admins}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Agents</CardTitle>
                                <Headphones className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{userStats.agents}</div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Today's Call Statistics */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4">Today's Performance</h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Calls</CardTitle>
                                <Phone className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{todayStats.total_calls}</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Answered Calls</CardTitle>
                                <PhoneCall className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-green-600">{todayStats.answered_calls}</div>
                                <p className="text-xs text-muted-foreground">
                                    {todayStats.total_calls > 0 
                                        ? `${((todayStats.answered_calls / todayStats.total_calls) * 100).toFixed(1)}% answer rate`
                                        : '0% answer rate'
                                    }
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Failed Calls</CardTitle>
                                <Phone className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-red-600">{todayStats.failed_calls}</div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Campaign Statistics by Product Type */}
                <div className="mb-8">
                    <h2 className="text-xl font-semibold mb-4">Campaign Statistics by Product</h2>
                    <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
                        {Object.values(campaignStats).map((stat) => (
                            <Card key={stat.product_type}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium">{stat.product_type}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{stat.total}</div>
                                    <p className="text-xs text-muted-foreground">campaigns</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Recent Campaigns and Top Agents */}
                <div className="grid gap-6 md:grid-cols-2 mb-8">
                    {/* Recent Campaigns */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Campaigns</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {recentCampaigns.map((campaign) => (
                                    <div key={campaign.id} className="flex justify-between items-center p-3 border rounded-lg">
                                        <div>
                                            <Link 
                                                href={`/campaign/${campaign.id}`}
                                                className="font-medium text-blue-600 hover:text-blue-800"
                                            >
                                                {campaign.campaign_name}
                                            </Link>
                                            <p className="text-sm text-gray-500">{campaign.product_type}</p>
                                            <p className="text-xs text-gray-400">
                                                {new Date(campaign.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <Badge className={getStatusBadgeColor(campaign.status)}>
                                                {campaign.status}
                                            </Badge>
                                            {campaign.nasbahs[0] && (
                                                <p className="text-xs text-gray-500 mt-1">
                                                    {campaign.nasbahs[0].called_numbers}/{campaign.nasbahs[0].total_numbers} called
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Top Performing Agents */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Top Performing Agents (30 days)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {topAgents.map((agent, index) => (
                                    <div key={agent.agent_id} className="flex justify-between items-center p-3 border rounded-lg">
                                        <div className="flex items-center gap-3">
                                            <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-sm font-bold">
                                                {index + 1}
                                            </div>
                                            <div>
                                                <p className="font-medium">{agent.agent.name}</p>
                                                <p className="text-sm text-gray-500">Agent</p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-bold text-green-600">{agent.total_answered}</p>
                                            <p className="text-xs text-gray-500">answered calls</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Weekly Performance Chart */}
                <div className="mb-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Weekly Performance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {weeklyPerformance.map((day) => (
                                    <div key={day.date} className="flex justify-between items-center p-2 border-b">
                                        <span className="text-sm font-medium">
                                            {new Date(day.date).toLocaleDateString()}
                                        </span>
                                        <div className="flex gap-4 text-sm">
                                            <span className="text-blue-600">{day.total_calls} total</span>
                                            <span className="text-green-600">{day.answered_calls} answered</span>
                                            <span className="text-gray-500">
                                                {day.total_calls > 0 
                                                    ? `${((day.answered_calls / day.total_calls) * 100).toFixed(1)}%`
                                                    : '0%'
                                                }
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Campaign Status Distribution */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">Campaign Status Distribution</h2>
                    <div className="grid gap-4 md:grid-cols-5">
                        {Object.values(campaignStatusStats).map((stat) => (
                            <Card key={stat.status}>
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-sm font-medium capitalize">{stat.status}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{stat.total}</div>
                                    <p className="text-xs text-muted-foreground">campaigns</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}