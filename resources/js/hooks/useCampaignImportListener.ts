import { useEchoPublic } from '@laravel/echo-react';
import { useEffect } from 'react';

interface CampaignImportEvent {
    campaignId: number;
}

export default function useCampaignImportListener(callback: (event: CampaignImportEvent) => void) {
    useEchoPublic('campaign-import', 'import.finished', callback);
}