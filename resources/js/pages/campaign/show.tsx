import AppLayout from '@/layouts/app-layout';
import { CampaignStats } from '@/components/campaign-stats';
import { CampaignControls } from '@/components/campaign-controls';
import { Head, usePage } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { useState, useEffect } from 'react';
import useCampaignStatusListener from '@/hooks/useCampaignStatusListener';

interface Campaign {
    id: number;
    campaign_name: string;
    product_type: string;
    created_by: string;
    created_at: string;
    status: 'pending' | 'running' | 'paused' | 'completed' | 'stopped';
    is_active: boolean;
    started_at?: string;
    stopped_at?: string;
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

export default function CampaignShow() {
    const { campaign: initialCampaign, stats: initialStats } = usePage().props as {
        campaign: Campaign;
        stats: Stats;
    };

    const [campaign, setCampaign] = useState<Campaign>(initialCampaign);
    const [stats, setStats] = useState<Stats>(initialStats);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Campaign', href: '/campaign' },
        { title: campaign.campaign_name, href: `/campaign/${campaign.id}` },
    ];

    // Listen for campaign status changes
    useCampaignStatusListener((event) => {
        if (event.campaign.id === campaign.id) {
            setCampaign(prev => ({ ...prev, ...event.campaign }));
        }
    });

    // Refresh stats periodically when campaign is running
    useEffect(() => {
        if (campaign.status === 'running') {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch(`/api/campaigns/${campaign.id}/status`);
                    if (response.ok) {
                        const data = await response.json();
                        setStats(data.stats);
                        setCampaign(data.campaign);
                    }
                } catch (error) {
                    console.error('Failed to fetch campaign stats:', error);
                }
            }, 5000); // Refresh every 5 seconds

            return () => clearInterval(interval);
        }
    }, [campaign.status, campaign.id]);

    const handleCampaignStatusChange = (updatedCampaign: Campaign) => {
        setCampaign(updatedCampaign);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Campaign: ${campaign.campaign_name}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="mb-8">
                    <div className="flex justify-between items-start mb-4">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">{campaign.campaign_name}</h1>
                            <p className="text-gray-600 mt-1">
                                Product: {campaign.product_type} • Created by: {campaign.created_by}
                            </p>
                            {campaign.started_at && (
                                <p className="text-sm text-gray-500 mt-1">
                                    Started: {new Date(campaign.started_at).toLocaleString()}
                                </p>
                            )}
                        </div>
                        <CampaignControls 
                            campaign={campaign} 
                            onStatusChange={handleCampaignStatusChange}
                        />
                    </div>
                </div>

                <CampaignStats stats={stats} />

                {/* Real-time Activity Log */}
                {campaign.status === 'running' && (
                    <div className="mt-8">
                        <div className="bg-white rounded-lg shadow border border-gray-200 p-6">
                            <h3 className="text-lg font-semibold mb-4">🔴 Live Activity</h3>
                            <div className="space-y-2 max-h-64 overflow-y-auto">
                                <div className="flex items-center gap-2 text-sm">
                                    <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                    <span className="text-gray-600">Predictive dialer is running...</span>
                                </div>
                                <div className="text-xs text-gray-500 ml-4">
                                    Last updated: {new Date().toLocaleTimeString()}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Campaign Details */}
                <div className="mt-8 grid gap-6 md:grid-cols-2">
                    <div className="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold mb-4">Campaign Information</h3>
                        <dl className="space-y-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Campaign Name</dt>
                                <dd className="text-sm text-gray-900">{campaign.campaign_name}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Product Type</dt>
                                <dd className="text-sm text-gray-900">{campaign.product_type}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Created By</dt>
                                <dd className="text-sm text-gray-900">{campaign.created_by}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Created At</dt>
                                <dd className="text-sm text-gray-900">
                                    {new Date(campaign.created_at).toLocaleString()}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div className="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <h3 className="text-lg font-semibold mb-4">Performance Summary</h3>
                        <dl className="space-y-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Total Numbers</dt>
                                <dd className="text-sm text-gray-900">{stats.total_numbers.toLocaleString()}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Completion Rate</dt>
                                <dd className="text-sm text-gray-900">
                                    {stats.total_numbers > 0 
                                        ? ((stats.called_numbers / stats.total_numbers) * 100).toFixed(1)
                                        : 0}%
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Answer Rate</dt>
                                <dd className="text-sm text-gray-900">
                                    {stats.total_calls > 0 
                                        ? ((stats.answered_calls / stats.total_calls) * 100).toFixed(1)
                                        : 0}%
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Status</dt>
                                <dd className="text-sm text-gray-900 capitalize">{campaign.status}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}