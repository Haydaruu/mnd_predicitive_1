<?php

namespace App\Exports;

use App\Models\Campaign;
use App\Models\Call;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CampaignSummaryExport implements WithMultipleSheets
{
    protected $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function sheets(): array
    {
        return [
            'Campaign Summary' => new CampaignSummarySheet($this->campaign),
            'Call Details' => new CampaignCallDetailsSheet($this->campaign),
            'Agent Performance' => new CampaignAgentPerformanceSheet($this->campaign),
        ];
    }
}

class CampaignSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function collection()
    {
        return collect([$this->campaign]);
    }

    public function headings(): array
    {
        return [
            'Campaign Name',
            'Product Type',
            'Created By',
            'Status',
            'Total Numbers',
            'Called Numbers',
            'Remaining Numbers',
            'Total Calls',
            'Answered Calls',
            'Failed Calls',
            'Busy Calls',
            'No Answer Calls',
            'Answer Rate (%)',
            'Completion Rate (%)',
            'Created At',
            'Started At',
            'Stopped At',
        ];
    }

    public function map($campaign): array
    {
        $totalNumbers = $campaign->nasbahs()->count();
        $calledNumbers = $campaign->nasbahs()->where('is_called', true)->count();
        $remainingNumbers = $totalNumbers - $calledNumbers;
        
        $totalCalls = $campaign->calls()->count();
        $answeredCalls = $campaign->calls()->where('status', 'answered')->count();
        $failedCalls = $campaign->calls()->where('status', 'failed')->count();
        $busyCalls = $campaign->calls()->where('status', 'busy')->count();
        $noAnswerCalls = $campaign->calls()->where('status', 'no_answer')->count();
        
        $answerRate = $totalCalls > 0 ? ($answeredCalls / $totalCalls) * 100 : 0;
        $completionRate = $totalNumbers > 0 ? ($calledNumbers / $totalNumbers) * 100 : 0;

        return [
            $campaign->campaign_name,
            $campaign->product_type,
            $campaign->created_by,
            ucfirst($campaign->status),
            $totalNumbers,
            $calledNumbers,
            $remainingNumbers,
            $totalCalls,
            $answeredCalls,
            $failedCalls,
            $busyCalls,
            $noAnswerCalls,
            round($answerRate, 2),
            round($completionRate, 2),
            $campaign->created_at->format('Y-m-d H:i:s'),
            $campaign->started_at ? $campaign->started_at->format('Y-m-d H:i:s') : 'N/A',
            $campaign->stopped_at ? $campaign->stopped_at->format('Y-m-d H:i:s') : 'N/A',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ]
            ],
        ];
    }
}

class CampaignCallDetailsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function collection()
    {
        return $this->campaign->calls()->with(['agent', 'nasbah', 'callerId'])->get();
    }

    public function headings(): array
    {
        return [
            'Call ID',
            'Agent',
            'Customer Name',
            'Phone Number',
            'Caller ID',
            'Status',
            'Disposition',
            'Call Started',
            'Call Ended',
            'Duration (seconds)',
            'Notes',
        ];
    }

    public function map($call): array
    {
        $duration = null;
        if ($call->call_started_at && $call->call_ended_at) {
            $duration = \Carbon\Carbon::parse($call->call_ended_at)
                ->diffInSeconds(\Carbon\Carbon::parse($call->call_started_at));
        }

        return [
            $call->id,
            $call->agent->name ?? 'N/A',
            $call->nasbah->name ?? 'N/A',
            $call->nasbah->phone ?? 'N/A',
            $call->callerId->number ?? 'N/A',
            ucfirst($call->status),
            ucfirst($call->disposition ?? 'N/A'),
            $call->call_started_at ? $call->call_started_at->format('Y-m-d H:i:s') : 'N/A',
            $call->call_ended_at ? $call->call_ended_at->format('Y-m-d H:i:s') : 'N/A',
            $duration ?? 0,
            $call->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ]
            ],
        ];
    }
}

class CampaignAgentPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function collection()
    {
        return $this->campaign->calls()
            ->with('agent')
            ->selectRaw('
                agent_id,
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = "answered" THEN 1 ELSE 0 END) as answered_calls,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_calls,
                SUM(CASE WHEN status = "busy" THEN 1 ELSE 0 END) as busy_calls,
                SUM(CASE WHEN status = "no_answer" THEN 1 ELSE 0 END) as no_answer_calls,
                SUM(CASE WHEN duration IS NOT NULL THEN duration ELSE 0 END) as total_talk_time,
                AVG(CASE WHEN duration IS NOT NULL THEN duration ELSE 0 END) as avg_talk_time
            ')
            ->groupBy('agent_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Agent Name',
            'Total Calls',
            'Answered Calls',
            'Failed Calls',
            'Busy Calls',
            'No Answer Calls',
            'Answer Rate (%)',
            'Total Talk Time (seconds)',
            'Average Talk Time (seconds)',
        ];
    }

    public function map($agentStats): array
    {
        $answerRate = $agentStats->total_calls > 0 
            ? ($agentStats->answered_calls / $agentStats->total_calls) * 100 
            : 0;

        return [
            $agentStats->agent->name ?? 'Unknown Agent',
            $agentStats->total_calls,
            $agentStats->answered_calls,
            $agentStats->failed_calls,
            $agentStats->busy_calls,
            $agentStats->no_answer_calls,
            round($answerRate, 2),
            $agentStats->total_talk_time,
            round($agentStats->avg_talk_time, 2),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ]
            ],
        ];
    }
}