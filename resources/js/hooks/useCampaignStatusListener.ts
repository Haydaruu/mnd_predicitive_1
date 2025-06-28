import { useEchoPublic } from '@laravel/echo-react';
import { useEffect } from 'react';

interface CampaignStatusEvent {
    campaign: {
        id: number;
        campaign_name: string;
        status: string;
        is_active: boolean;
    };
}

export default function useCampaignStatusListener(callback: (event: CampaignStatusEvent) => void) {
    useEchoPublic('campaign-status', 'status.changed', callback);
}