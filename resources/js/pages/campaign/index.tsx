import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { CampaignControls } from '@/components/campaign-controls';
import { Head, Link, usePage } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { useState, useEffect } from 'react';
import useCampaignStatusListener from '@/hooks/useCampaignStatusListener';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campaign',
        href: '/campaign',
    },
];

interface Campaign {
    id: number;
    campaign_name: string;
    product_type: string;
    created_by: string;
    created_at: string;
    status: 'pending' | 'running' | 'paused' | 'completed' | 'stopped';
    is_active: boolean;
    nasbahs_count: number;
}

interface CampaignsData {
    data: Campaign[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export default function CampaignIndex() {
    const { campaigns: initialCampaigns } = usePage().props as {
        campaigns: CampaignsData;
    };

    const [campaigns, setCampaigns] = useState<CampaignsData>(initialCampaigns);

    // Listen for campaign status changes
    useCampaignStatusListener((event) => {
        setCampaigns(prev => ({
            ...prev,
            data: prev.data.map(campaign => 
                campaign.id === event.campaign.id 
                    ? { ...campaign, ...event.campaign }
                    : campaign
            )
        }));
    });

    const handleCampaignStatusChange = (updatedCampaign: Campaign) => {
        setCampaigns(prev => ({
            ...prev,
            data: prev.data.map(campaign => 
                campaign.id === updatedCampaign.id 
                    ? updatedCampaign
                    : campaign
            )
        }));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Campaign List" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">📋 Campaign Management</h1>
                    <div className="flex gap-2">
                        <Link href="/reports/dashboard">
                            <Button variant="outline">
                                📊 Reports
                            </Button>
                        </Link>
                        <Link href="/campaign/upload">
                            <Button>
                                + Upload Campaign
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="overflow-auto rounded-lg shadow border border-gray-200">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">#</th>
                                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Campaign Name</th>
                                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Product Type</th>
                                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Numbers</th>
                                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Created By</th>
                                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 bg-white">
                            {campaigns.data.length > 0 ? (
                                campaigns.data.map((campaign, i) => (
                                    <tr key={campaign.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 text-sm text-gray-600">{i + 1}</td>
                                        <td className="px-6 py-4 text-sm text-gray-800 font-medium">
                                            <Link 
                                                href={`/campaign/${campaign.id}`}
                                                className="text-blue-600 hover:text-blue-800"
                                            >
                                                {campaign.campaign_name}
                                            </Link>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-800">{campaign.product_type}</td>
                                        <td className="px-6 py-4 text-sm text-gray-800">{campaign.nasbahs_count}</td>
                                        <td className="px-6 py-4 text-sm text-gray-800">{campaign.created_by}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            <CampaignControls 
                                                campaign={campaign} 
                                                onStatusChange={handleCampaignStatusChange}
                                            />
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            <div className="flex gap-1">
                                                <Link href={`/campaign/${campaign.id}`}>
                                                    <Button size="sm" variant="outline">
                                                        View
                                                    </Button>
                                                </Link>
                                                <Link href={`/reports/campaign/${campaign.id}`}>
                                                    <Button size="sm" variant="outline">
                                                        Reports
                                                    </Button>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7} className="text-center py-8 text-gray-500">
                                        No campaigns found. 
                                        <Link href="/campaign/upload" className="text-blue-600 hover:text-blue-800 ml-1">
                                            Upload your first campaign
                                        </Link>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {campaigns.links && campaigns.links.length > 3 && (
                    <div className="flex justify-center mt-6">
                        <nav className="flex space-x-1">
                            {campaigns.links.map((link, index) => (
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