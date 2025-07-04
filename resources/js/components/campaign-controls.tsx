import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Play, Pause, Square, RotateCcw } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface Campaign {
    id: number;
    campaign_name: string;
    status: 'pending' | 'running' | 'paused' | 'completed' | 'stopped';
    is_active: boolean;
}

interface CampaignControlsProps {
    campaign: Campaign;
    onStatusChange?: (campaign: Campaign) => void;
}

export function CampaignControls({ campaign, onStatusChange }: CampaignControlsProps) {
    const [isLoading, setIsLoading] = useState(false);

    const handleAction = async (action: string) => {
        setIsLoading(true);
        
        try {
            // Use relative URL for same-origin request
            const response = await fetch(`/api/campaigns/${campaign.id}/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response error:', response.status, errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const data = await response.json();

            if (data.success) {
                onStatusChange?.(data.campaign);
                
                // Show success message
                console.log(`✅ Campaign ${action} successfully!`);
                
                // Reload page data
                router.reload({ only: ['campaigns', 'campaign'] });
            } else {
                const errorMessage = data.message || `Failed to ${action} campaign`;
                console.error(`❌ ${errorMessage}`);
                alert(errorMessage);
            }
        } catch (error) {
            console.error(`❌ Action failed:`, error);
            alert(`Action failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
        } finally {
            setIsLoading(false);
        }
    };

    const getStatusBadge = (status: string) => {
        const variants = {
            pending: 'secondary',
            running: 'default',
            paused: 'outline',
            completed: 'secondary',
            stopped: 'destructive',
        } as const;

        const colors = {
            pending: 'bg-gray-100 text-gray-800',
            running: 'bg-green-100 text-green-800',
            paused: 'bg-yellow-100 text-yellow-800',
            completed: 'bg-blue-100 text-blue-800',
            stopped: 'bg-red-100 text-red-800',
        } as const;

        return (
            <Badge variant={variants[status as keyof typeof variants] || 'secondary'} className={colors[status as keyof typeof colors]}>
                {status.toUpperCase()}
            </Badge>
        );
    };

    return (
        <div className="flex items-center gap-2">
            {getStatusBadge(campaign.status)}
            
            <div className="flex gap-1">
                {(campaign.status === 'pending' || campaign.status === 'stopped') && (
                    <Button
                        size="sm"
                        onClick={() => handleAction('start')}
                        disabled={isLoading}
                        className="h-8 w-8 p-0"
                        title="Start Campaign"
                    >
                        <Play className="h-4 w-4" />
                    </Button>
                )}

                {campaign.status === 'running' && (
                    <>
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleAction('pause')}
                            disabled={isLoading}
                            className="h-8 w-8 p-0"
                            title="Pause Campaign"
                        >
                            <Pause className="h-4 w-4" />
                        </Button>
                        <Button
                            size="sm"
                            variant="destructive"
                            onClick={() => handleAction('stop')}
                            disabled={isLoading}
                            className="h-8 w-8 p-0"
                            title="Stop Campaign"
                        >
                            <Square className="h-4 w-4" />
                        </Button>
                    </>
                )}

                {campaign.status === 'paused' && (
                    <>
                        <Button
                            size="sm"
                            onClick={() => handleAction('resume')}
                            disabled={isLoading}
                            className="h-8 w-8 p-0"
                            title="Resume Campaign"
                        >
                            <Play className="h-4 w-4" />
                        </Button>
                        <Button
                            size="sm"
                            variant="destructive"
                            onClick={() => handleAction('stop')}
                            disabled={isLoading}
                            className="h-8 w-8 p-0"
                            title="Stop Campaign"
                        >
                            <Square className="h-4 w-4" />
                        </Button>
                    </>
                )}
            </div>
        </div>
    );
}