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

class CustomerContactsSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function array(): array
    {
        $contacts = $this->customer->contacts;
        
        if ($contacts->isEmpty()) {
            return [['No contacts available']];
        }

        return $contacts->map(function ($contact) {
            return [
                $contact->name,
                $contact->position ?? '-',
                $contact->email ?? '-',
                $contact->whatsapp ?? '-',
                $contact->is_primary ? 'Yes' : 'No',
                $contact->notes ?? '-',
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Position',
            'Email',
            'WhatsApp',
            'Primary Contact',
            'Notes',
        ];
    }

    public function title(): string
    {
        return 'Contacts';
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
