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

class CustomerInvoicesSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function array(): array
    {
        $invoices = $this->customer->invoices;
        
        if ($invoices->isEmpty()) {
            return [['No invoices available']];
        }

        $rows = [];
        
        foreach ($invoices as $invoice) {
            // Invoice header row
            $rows[] = [
                $invoice->invoice_number,
                $invoice->invoice_date?->format('Y-m-d'),
                $invoice->due_date?->format('Y-m-d') ?? '-',
                strtoupper($invoice->status),
                number_format($invoice->subtotal, 0, ',', '.'),
                number_format($invoice->tax, 0, ',', '.'),
                number_format($invoice->total, 0, ',', '.'),
                $invoice->notes ?? '-',
            ];
            
            // Invoice items if any
            if ($invoice->items && $invoice->items->isNotEmpty()) {
                $rows[] = ['', 'Items:', '', '', '', '', '', ''];
                foreach ($invoice->items as $item) {
                    $rows[] = [
                        '',
                        '  - ' . $item->description,
                        '',
                        '',
                        $item->quantity . ' x ' . number_format($item->unit_price, 0, ',', '.'),
                        '',
                        number_format($item->subtotal, 0, ',', '.'),
                        '',
                    ];
                }
                $rows[] = ['', '', '', '', '', '', '', '']; // Empty row separator
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Invoice Date',
            'Due Date',
            'Status',
            'Subtotal (Rp)',
            'Tax (Rp)',
            'Total (Rp)',
            'Notes',
        ];
    }

    public function title(): string
    {
        return 'Sales History';
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
