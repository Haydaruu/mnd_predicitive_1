import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { CampaignControls } from '@/components/campaign-controls';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { useState, useEffect } from 'react';
import { Trash2, Users, RefreshCw } from 'lucide-react';
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
    status: 'pending' | 'uploading' | 'processing' | 'running' | 'paused' | 'completed' | 'stopped' | 'failed';
    is_active: boolean;
    nasbahs_count: number;
    keterangan?: string;
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

    const handleDelete = (campaignId: number) => {
        if (confirm('Are you sure you want to delete this campaign? This action cannot be undone and will delete all associated data.')) {
            router.delete(`/campaign/${campaignId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setCampaigns(prev => ({
                        ...prev,
                        data: prev.data.filter(campaign => campaign.id !== campaignId)
                    }));
                },
            });
        }
    };

    const refreshPage = () => {
        router.reload({ only: ['campaigns'] });
    };

    const getStatusBadgeColor = (status: string) => {
        switch (status) {
            case 'uploading':
                return 'bg-blue-100 text-blue-800';
            case 'processing':
                return 'bg-yellow-100 text-yellow-800';
            case 'running':
                return 'bg-green-100 text-green-800';
            case 'paused':
                return 'bg-orange-100 text-orange-800';
            case 'completed':
                return 'bg-purple-100 text-purple-800';
            case 'stopped':
                return 'bg-gray-100 text-gray-800';
            case 'failed':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Campaign List" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">📋 Campaign Management</h1>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={refreshPage}>
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Refresh
                        </Button>
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
                                            {campaign.keterangan && (
                                                <p className="text-xs text-red-600 mt-1">{campaign.keterangan}</p>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-800">{campaign.product_type}</td>
                                        <td className="px-6 py-4 text-sm text-gray-800">
                                            {campaign.status === 'processing' || campaign.status === 'uploading' 
                                                ? 'Processing...' 
                                                : campaign.nasbahs_count.toLocaleString()
                                            }
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-800">{campaign.created_by}</td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            <div className="flex flex-col gap-2">
                                                <span className={`px-2 py-1 text-xs rounded-full ${getStatusBadgeColor(campaign.status)}`}>
                                                    {campaign.status.toUpperCase()}
                                                </span>
                                                {(campaign.status === 'pending' || campaign.status === 'running' || campaign.status === 'paused') && (
                                                    <CampaignControls 
                                                        campaign={campaign} 
                                                        onStatusChange={handleCampaignStatusChange}
                                                    />
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-500">
                                            <div className="flex gap-1">
                                                {campaign.status !== 'uploading' && campaign.status !== 'processing' && (
                                                    <>
                                                        <Link href={`/campaign/${campaign.id}`}>
                                                            <Button size="sm" variant="outline">
                                                                View
                                                            </Button>
                                                        </Link>
                                                        {campaign.nasbahs_count > 0 && (
                                                            <Link href={`/campaign/${campaign.id}/nasbahs`}>
                                                                <Button size="sm" variant="outline">
                                                                    <Users className="h-4 w-4 mr-1" />
                                                                    Customers
                                                                </Button>
                                                            </Link>
                                                        )}
                                                        <Link href={`/reports/campaign/${campaign.id}`}>
                                                            <Button size="sm" variant="outline">
                                                                Reports
                                                            </Button>
                                                        </Link>
                                                    </>
                                                )}
                                                <Button 
                                                    size="sm" 
                                                    variant="destructive"
                                                    onClick={() => handleDelete(campaign.id)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
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