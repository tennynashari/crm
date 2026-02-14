<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerInteractionsSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function array(): array
    {
        $interactions = $this->customer->interactions;
        
        if ($interactions->isEmpty()) {
            return [['No interactions available']];
        }

        return $interactions->map(function ($interaction) {
            return [
                $interaction->interaction_at?->format('Y-m-d H:i:s'),
                $interaction->interaction_type,
                $interaction->channel ?? '-',
                $interaction->subject ?? '-',
                $interaction->summary ?? $interaction->content ?? '-',
                $interaction->createdByUser?->name ?? '-',
                $interaction->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Date & Time',
            'Type',
            'Channel',
            'Subject',
            'Summary',
            'Created By',
            'Recorded At',
        ];
    }

    public function title(): string
    {
        return 'Communication History';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
            ],
        ];
    }
}
