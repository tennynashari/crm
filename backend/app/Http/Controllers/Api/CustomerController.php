<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Interaction;
use App\Models\AuditLog;
use App\Exports\CustomersExport;
use App\Exports\CustomerDetailExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Customer::with([
            'area', 
            'assignedSales', 
            'leadStatus', 
            'contacts',
            'interactions' => function ($q) {
                $q->latest('interaction_at')->limit(1);
            }
        ]);

        // Role-based filtering: sales only see their assigned customers
        if ($user->role !== 'admin') {
            $query->where('assigned_sales_id', $user->id);
        }

        // Filter by area
        if ($request->has('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        // Filter by lead status
        if ($request->has('lead_status_id')) {
            $query->where('lead_status_id', $request->lead_status_id);
        }

        // Filter by assigned sales (admin can filter by any sales)
        if ($request->has('assigned_sales_id') && $user->role === 'admin') {
            $query->where('assigned_sales_id', $request->assigned_sales_id);
        }

        // Filter by source
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        // Filter by next action status
        $hasNextActionFilter = false;
        if ($request->has('next_action_status')) {
            $hasNextActionFilter = true;
            if ($request->next_action_status === 'today') {
                $query->whereDate('next_action_date', now());
            } elseif ($request->next_action_status === 'this_week') {
                $query->whereBetween('next_action_date', [
                    now()->startOfDay(),
                    now()->addDays(7)->endOfDay()
                ]);
            } elseif ($request->next_action_status === 'meeting') {
                $query->where('next_action_plan', 'ilike', '%meeting%')
                      ->whereDate('next_action_date', '>=', now()->toDateString());
            }
        }

        // Sorting - support sort_by and sort_order
        $sortBy = $request->get('sort_by', 'next_action_date');
        $sortOrder = $request->get('sort_order', 'asc');
        
        // Validate sort_by values
        $allowedSortBy = ['next_action_date', 'last_interaction_date', 'created_at'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'next_action_date';
        }
        
        // Validate sort_order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        // Apply sorting based on sort_by parameter
        if ($sortBy === 'last_interaction_date') {
            // Sort by latest interaction date - need to use subquery
            $query->leftJoin('interactions', function ($join) {
                $join->on('customers.id', '=', 'interactions.customer_id')
                    ->whereRaw('interactions.id = (select id from interactions where customer_id = customers.id order by interaction_at desc limit 1)');
            })
            ->orderBy('interactions.interaction_at', $sortOrder)
            ->select('customers.*');
        } else {
            // Sort by next_action_date or created_at
            $query->orderBy($sortBy, $sortOrder);
        }

        $customers = $query->paginate($request->get('per_page', 15));
        
        // Explicitly load latest interaction for each customer after pagination
        foreach ($customers as $customer) {
            $latestInteraction = $customer->interactions()
                ->latest('interaction_at')
                ->first();
            $customer->setRelation('interactions', $latestInteraction ? collect([$latestInteraction]) : collect([]));
        }

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:255',
            'is_individual' => 'boolean',
            'area_id' => 'nullable|exists:areas,id',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'source' => 'required|in:inbound,outbound',
            'assigned_sales_id' => 'nullable|exists:users,id',
            'lead_status_id' => 'nullable|exists:lead_statuses,id',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        // Log creation
        AuditLog::create([
            'user_id' => auth()->id(),
            'customer_id' => $customer->id,
            'action' => 'customer_created',
            'model_type' => 'Customer',
            'model_id' => $customer->id,
            'new_values' => $validated,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'customer' => $customer->load(['area', 'assignedSales', 'leadStatus', 'contacts']),
            'message' => 'Customer created successfully',
        ], 201);
    }

    public function show($id)
    {
        $customer = Customer::with([
            'area',
            'assignedSales',
            'leadStatus',
            'contacts',
            'interactions' => function ($query) {
                $query->orderBy('interaction_at', 'desc');
            },
            'interactions.createdByUser',
            'interactions.leadStatusSnapshot'
        ])->findOrFail($id);

        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $validated = $request->validate([
            'company' => 'sometimes|required|string|max:255',
            'is_individual' => 'boolean',
            'area_id' => 'nullable|exists:areas,id',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'source' => 'sometimes|required|in:inbound,outbound',
            'assigned_sales_id' => 'nullable|exists:users,id',
            'lead_status_id' => 'nullable|exists:lead_statuses,id',
            'notes' => 'nullable|string',
        ]);

        $oldValues = $customer->only(array_keys($validated));
        $customer->update($validated);

        // Log significant changes
        if (isset($validated['lead_status_id']) && $oldValues['lead_status_id'] != $validated['lead_status_id']) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'action' => 'lead_status_changed',
                'model_type' => 'Customer',
                'model_id' => $customer->id,
                'old_values' => ['lead_status_id' => $oldValues['lead_status_id']],
                'new_values' => ['lead_status_id' => $validated['lead_status_id']],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        if (isset($validated['area_id']) && $oldValues['area_id'] != $validated['area_id']) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'action' => 'area_changed',
                'model_type' => 'Customer',
                'model_id' => $customer->id,
                'old_values' => ['area_id' => $oldValues['area_id']],
                'new_values' => ['area_id' => $validated['area_id']],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return response()->json([
            'customer' => $customer->load(['area', 'assignedSales', 'leadStatus', 'contacts']),
            'message' => 'Customer updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully',
        ]);
    }

    public function updateNextAction(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'next_action_date' => 'nullable|date',
            'next_action_plan' => 'nullable|string',
            'next_action_priority' => 'nullable|in:low,medium,high',
            'next_action_status' => 'nullable|in:pending,done,overdue',
        ]);

        $oldValues = $customer->only(array_keys($validated));
        $customer->update($validated);

        // Log next action update
        AuditLog::create([
            'user_id' => auth()->id(),
            'customer_id' => $customer->id,
            'action' => 'next_action_updated',
            'model_type' => 'Customer',
            'model_id' => $customer->id,
            'old_values' => $oldValues,
            'new_values' => $validated,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'customer' => $customer,
            'message' => 'Next action updated successfully',
        ]);
    }

    /**
     * Export customers to Excel
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        
        // Get filters from request
        $filters = $request->only([
            'area_id',
            'lead_status_id',
            'assigned_sales_id',
            'source',
            'search',
            'next_action_status'
        ]);

        $fileName = 'customers_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new CustomersExport($filters, $user),
            $fileName
        );
    }

    /**
     * Export single customer detail to Excel
     */
    public function exportDetail($id)
    {
        $customer = Customer::findOrFail($id);
        
        $fileName = 'customer_' . $customer->id . '_' . str_replace(' ', '_', $customer->company) . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new CustomerDetailExport($id),
            $fileName
        );
    }
}
