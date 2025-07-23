<?php

namespace App\Exports;

use App\Models\CallReport;
use App\Models\Call;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class CallReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Call::with(['campaign', 'agent', 'nasbah', 'callerId'])
            ->orderBy('call_started_at', 'desc');

        if (isset($this->filters['campaign_id'])) {
            $query->where('campaign_id', $this->filters['campaign_id']);
        }

        if (isset($this->filters['agent_id'])) {
            $query->where('agent_id', $this->filters['agent_id']);
        }

        if (isset($this->filters['date_from'])) {
            $query->whereDate('call_started_at', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->whereDate('call_started_at', '<=', $this->filters['date_to']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Call ID',
            'Campaign',
            'Agent',
            'Customer Name',
            'Phone Number',
            'Caller ID',
            'Status',
            'Disposition',
            'Call Started',
            'Call Ended',
            'Duration (seconds)',
            'Talk Time',
            'Notes',
            'Outstanding',
            'Penalty',
        ];
    }

    public function map($call): array
    {
        $duration = null;
        $talkTime = '';

        if ($call->call_started_at && $call->call_ended_at) {
            $start = Carbon::parse($call->call_started_at);
            $end = Carbon::parse($call->call_ended_at);
            $duration = $end->diffInSeconds($start);
            
            if ($duration > 0) {
                $hours = floor($duration / 3600);
                $minutes = floor(($duration % 3600) / 60);
                $seconds = $duration % 60;
                $talkTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }
        }

        return [
            $call->id,
            $call->campaign->campaign_name ?? 'N/A',
            $call->agent->name ?? 'N/A',
            $call->nasbah->name ?? 'N/A',
            $call->nasbah->phone ?? 'N/A',
            $call->callerId->number ?? 'N/A',
            ucfirst($call->status),
            ucfirst($call->disposition ?? 'N/A'),
            $call->call_started_at ? Carbon::parse($call->call_started_at)->format('Y-m-d H:i:s') : 'N/A',
            $call->call_ended_at ? Carbon::parse($call->call_ended_at)->format('Y-m-d H:i:s') : 'N/A',
            $duration ?? 0,
            $talkTime,
            $call->notes ?? '',
            $call->nasbah->outstanding ?? 0,
            $call->nasbah->denda ?? 0,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
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
