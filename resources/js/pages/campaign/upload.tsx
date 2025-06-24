import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Download } from 'lucide-react';
import useCampaignImportListener from '@/hooks/useCampaignImportListener';

export default function UploadCampaign() {
  const [campaignName, setCampaignName] = useState('');
  const [productType, setProductType] = useState('akulaku');
  const [file, setFile] = useState<File | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useCampaignImportListener((e) => {
    console.log('📢 Campaign import selesai:', e);
    alert(`✅ Import selesai untuk Campaign ID: ${e.campaignId}`);
    router.visit('/campaign');
  });

  const downloadTemplate = () => {
    window.open(`/campaign/template/download?product_type=${productType}`, '_blank');
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!file) {
      alert('Mohon upload file Excel terlebih dahulu.');
      return;
    }

    setIsSubmitting(true);
    
    const formData = new FormData();
    formData.append('campaign_name', campaignName);
    formData.append('product_type', productType);
    formData.append('file', file);

    router.post(route('campaign.upload'), formData, {
      forceFormData: true,
      preserveScroll: true,
      onError: (errors) => {
        setIsSubmitting(false);
        if (errors.file) {
          alert(`Upload gagal: ${errors.file}`);
        }
      },
      onSuccess: () => {
        setIsSubmitting(false);
        alert("Upload berhasil!");
        router.visit('/campaign');
      }
    });
  };

  return (
    <AppLayout breadcrumbs={[{ title: 'Campaign', href: '/campaign' }]}>
      <Head title="Upload Campaign" />

      <div className="flex justify-center items-center min-h-[calc(100vh-120px)] px-4">
        <div className="w-full max-w-lg bg-white rounded-2xl shadow-xl p-8 space-y-6 border border-gray-200">
          <h2 className="text-2xl font-bold text-gray-800 text-center">📤 Upload Campaign</h2>

          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
              <input
                type="text"
                value={campaignName}
                onChange={(e) => setCampaignName(e.target.value)}
                placeholder="Nama Campaign"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-indigo-200 focus:outline-none"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Product Type</label>
              <Select value={productType} onValueChange={setProductType}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select product type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="akulaku">Akulaku</SelectItem>
                  <SelectItem value="BNI">BNI</SelectItem>
                  <SelectItem value="BRI">BRI</SelectItem>
                  <SelectItem value="CashWagon">CashWagon</SelectItem>
                  <SelectItem value="MauCash">MauCash</SelectItem>
                  <SelectItem value="KoinWorks">KoinWorks</SelectItem>
                  <SelectItem value="KP+">KP+</SelectItem>
                  <SelectItem value="PinjamYuk">PinjamYuk</SelectItem>
                  <SelectItem value="UangMe">UangMe</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <div className="flex justify-between items-center mb-2">
                <label className="block text-sm font-medium text-gray-700">Upload File</label>
                <Button 
                  type="button" 
                  variant="outline" 
                  size="sm"
                  onClick={downloadTemplate}
                >
                  <Download className="h-4 w-4 mr-1" />
                  Download Template
                </Button>
              </div>
              <input
                type="file"
                accept=".xlsx,.xls,.csv"
                onChange={(e) => {
                  if (e.target.files?.[0]) {
                    setFile(e.target.files[0]);
                  }
                }}
                className="block w-full text-sm text-gray-700
                          file:mr-4 file:py-2 file:px-4
                          file:rounded-lg file:border-0
                          file:text-sm file:font-semibold
                          file:bg-indigo-50 file:text-indigo-700
                          hover:file:bg-indigo-100"
                required
              />
              <p className="text-xs text-gray-500 mt-1">Format yang didukung: .csv, .xlsx, .xls</p>
            </div>

            <div className="pt-4 text-right">
              <Button type="submit" className="px-6 py-2" disabled={isSubmitting}>
                {isSubmitting ? (
                  <span className="flex items-center gap-2">
                    <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Uploading...
                  </span>
                ) : 'Upload'}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}