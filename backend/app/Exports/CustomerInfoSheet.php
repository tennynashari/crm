<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerInfoSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function array(): array
    {
        return [
            [
                $this->customer->company,
                $this->customer->is_individual ? 'Yes' : 'No',
                $this->customer->email ?? '-',
                $this->customer->phone ?? '-',
                $this->customer->address ?? '-',
                $this->customer->area?->name ?? '-',
                $this->customer->assignedSales?->name ?? '-',
                $this->customer->leadStatus?->name ?? '-',
                ucfirst($this->customer->source),
                $this->customer->next_action_date ?? '-',
                ucfirst($this->customer->next_action_priority ?? '-'),
                $this->customer->next_action_plan ?? '-',
                $this->customer->notes ?? '-',
                $this->customer->created_at?->format('Y-m-d H:i:s'),
                $this->customer->updated_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Company',
            'Is Individual',
            'Email',
            'Phone',
            'Address',
            'Area',
            'Assigned Sales',
            'Lead Status',
            'Source',
            'Next Action Date',
            'Next Action Priority',
            'Next Action Plan',
            'Notes',
            'Created At',
            'Updated At',
        ];
    }

    public function title(): string
    {
        return 'Customer Info';
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
