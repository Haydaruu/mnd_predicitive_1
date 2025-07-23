import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Head, usePage, router } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { useState } from 'react';
import { Search, Download, Trash2 } from 'lucide-react';

interface Campaign {
    id: number;
    campaign_name: string;
    product_type: string;
}

interface Nasbah {
    id: number;
    name: string;
    phone: string;
    outstanding: number;
    denda: number;
    is_called: boolean;
    created_at: string;
    data_json?: string;
}

export default function CampaignNasbahs() {
    const { campaign, nasbahs } = usePage().props as {
        campaign: Campaign;
        nasbahs: {
            data: Nasbah[];
            links: any[];
        };
    };

    const [searchTerm, setSearchTerm] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Campaign', href: '/campaign' },
        { title: campaign.campaign_name, href: `/campaign/${campaign.id}` },
        { title: 'Customer Data', href: `/campaign/${campaign.id}/nasbahs` },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(`/campaign/${campaign.id}/nasbahs`, { search: searchTerm }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (nasbahId: number) => {
        if (confirm('Are you sure you want to delete this customer data?')) {
            router.delete(`/campaign/${campaign.id}/nasbahs/${nasbahId}`, {
                preserveScroll: true,
            });
        }
    };

    const exportData = () => {
        window.open(`/campaign/${campaign.id}/nasbahs/export`, '_blank');
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Customer Data - ${campaign.campaign_name}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">👥 Customer Data</h1>
                        <p className="text-gray-600 mt-1">Campaign: {campaign.campaign_name} ({campaign.product_type})</p>
                    </div>
                    <Button onClick={exportData} variant="outline">
                        <Download className="h-4 w-4 mr-2" />
                        Export Excel
                    </Button>
                </div>

                {/* Search */}
                <div className="mb-6">
                    <form onSubmit={handleSearch} className="flex gap-2 max-w-md">
                        <Input
                            type="text"
                            placeholder="Search by name or phone..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                        <Button type="submit" variant="outline">
                            <Search className="h-4 w-4" />
                        </Button>
                    </form>
                </div>

                {/* Customer Data Table */}
                <div className="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Phone
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Outstanding
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Penalty
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Added
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {nasbahs.data.map((nasbah) => (
                                <tr key={nasbah.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm font-medium text-gray-900">{nasbah.name}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900">{nasbah.phone}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900">{formatCurrency(nasbah.outstanding)}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900">{formatCurrency(nasbah.denda)}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <Badge className={nasbah.is_called ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}>
                                            {nasbah.is_called ? 'Called' : 'Not Called'}
                                        </Badge>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {new Date(nasbah.created_at).toLocaleDateString()}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <Button 
                                            size="sm" 
                                            variant="destructive"
                                            onClick={() => handleDelete(nasbah.id)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {nasbahs.data.length === 0 && (
                        <div className="text-center py-8 text-gray-500">
                            No customer data found.
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {nasbahs.links && nasbahs.links.length > 3 && (
                    <div className="flex justify-center mt-6">
                        <nav className="flex space-x-1">
                            {nasbahs.links.map((link, index) => (
                                <a
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