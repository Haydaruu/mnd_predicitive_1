import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Phone, PhoneCall, PhoneOff, Clock } from 'lucide-react';

interface CampaignStatsProps {
    stats: {
        total_numbers: number;
        called_numbers: number;
        remaining_numbers: number;
        total_calls: number;
        answered_calls: number;
        failed_calls: number;
        busy_calls: number;
        no_answer_calls: number;
    };
}

export function CampaignStats({ stats }: CampaignStatsProps) {
    const progressPercentage = stats.total_numbers > 0 
        ? (stats.called_numbers / stats.total_numbers) * 100 
        : 0;

    const answerRate = stats.total_calls > 0 
        ? (stats.answered_calls / stats.total_calls) * 100 
        : 0;

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Progress</CardTitle>
                    <Phone className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{stats.called_numbers}/{stats.total_numbers}</div>
                    <Progress value={progressPercentage} className="mt-2" />
                    <p className="text-xs text-muted-foreground mt-1">
                        {progressPercentage.toFixed(1)}% completed
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Answered Calls</CardTitle>
                    <PhoneCall className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{stats.answered_calls}</div>
                    <p className="text-xs text-muted-foreground">
                        {answerRate.toFixed(1)}% answer rate
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Failed Calls</CardTitle>
                    <PhoneOff className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{stats.failed_calls}</div>
                    <p className="text-xs text-muted-foreground">
                        {stats.busy_calls} busy, {stats.no_answer_calls} no answer
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">Remaining</CardTitle>
                    <Clock className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">{stats.remaining_numbers}</div>
                    <p className="text-xs text-muted-foreground">
                        Numbers to call
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}