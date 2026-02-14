<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;
    protected $user;

    public function __construct($filters, $user)
    {
        $this->filters = $filters;
        $this->user = $user;
    }

    public function query()
    {
        $query = Customer::query()
            ->with(['area', 'assignedSales', 'leadStatus', 'contacts']);

        // Role-based filtering
        if ($this->user->role !== 'admin') {
            $query->where('assigned_sales_id', $this->user->id);
        }

        // Apply filters
        if (!empty($this->filters['area_id'])) {
            $query->where('area_id', $this->filters['area_id']);
        }

        if (!empty($this->filters['lead_status_id'])) {
            $query->where('lead_status_id', $this->filters['lead_status_id']);
        }

        if (!empty($this->filters['assigned_sales_id']) && $this->user->role === 'admin') {
            $query->where('assigned_sales_id', $this->filters['assigned_sales_id']);
        }

        if (!empty($this->filters['source'])) {
            $query->where('source', $this->filters['source']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('company', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        if (!empty($this->filters['next_action_status'])) {
            if ($this->filters['next_action_status'] === 'today') {
                $query->whereDate('next_action_date', now());
            } elseif ($this->filters['next_action_status'] === 'this_week') {
                $query->whereBetween('next_action_date', [
                    now()->startOfDay(),
                    now()->addDays(7)->endOfDay()
                ]);
            } elseif ($this->filters['next_action_status'] === 'meeting') {
                $query->where('next_action_plan', 'ilike', '%meeting%')
                      ->whereDate('next_action_date', '>=', now()->toDateString());
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Company',
            'Contact Person',
            'Email',
            'Phone',
            'Address',
            'Area',
            'Lead Status',
            'Source',
            'Assigned Sales',
            'Next Action Date',
            'Next Action Plan',
            'Is Individual',
            'Created At',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->company,
            $customer->contact_person,
            $customer->email,
            $customer->phone,
            $customer->address,
            $customer->area ? $customer->area->name : '-',
            $customer->leadStatus ? $customer->leadStatus->name : '-',
            ucfirst($customer->source),
            $customer->assignedSales ? $customer->assignedSales->name : '-',
            $customer->next_action_date ? $customer->next_action_date->format('Y-m-d') : '-',
            $customer->next_action_plan ?? '-',
            $customer->is_individual ? 'Yes' : 'No',
            $customer->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ]
            ],
        ];
    }
}
