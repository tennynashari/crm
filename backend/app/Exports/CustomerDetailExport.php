<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CustomerDetailExport implements WithMultipleSheets
{
    protected $customer;

    public function __construct($customerId)
    {
        $this->customer = Customer::with([
            'area',
            'assignedSales',
            'leadStatus',
            'contacts',
            'interactions' => function ($query) {
                $query->orderBy('interaction_at', 'desc');
            },
            'interactions.createdByUser',
            'invoices.items'
        ])->findOrFail($customerId);
    }

    public function sheets(): array
    {
        return [
            new CustomerInfoSheet($this->customer),
            new CustomerContactsSheet($this->customer),
            new CustomerInteractionsSheet($this->customer),
            new CustomerInvoicesSheet($this->customer),
        ];
    }
}
