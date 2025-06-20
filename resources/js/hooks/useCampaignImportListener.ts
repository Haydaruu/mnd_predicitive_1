import { useEcho } from '@laravel/echo-react';
import { useEffect } from 'react';

interface CampaignImportEvent {
    campaignId: number;
}

export default function useCampaignImportListener(callback: (event: CampaignImportEvent) => void) {
    useEcho('campaign-import', 'import.finished', callback);
}