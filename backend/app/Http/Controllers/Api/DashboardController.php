<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\LeadStatus;
use App\Models\Area;
use App\Models\Interaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $userId = $request->user()->id;
        $role = $request->user()->role;

        // Base query - if sales, only their customers
        $customerQuery = Customer::query();
        if ($role === 'sales') {
            $customerQuery->where('assigned_sales_id', $userId);
        }

        // Total customers per area
        $customersByArea = (clone $customerQuery)
            ->select('area_id', \DB::raw('count(*) as total'))
            ->groupBy('area_id')
            ->with('area')
            ->get()
            ->map(function ($item) {
                return [
                    'area' => $item->area ? $item->area->name : 'No Area',
                    'total' => $item->total,
                ];
            });

        // Leads by status
        $leadsByStatus = (clone $customerQuery)
            ->select('lead_status_id', \DB::raw('count(*) as total'))
            ->groupBy('lead_status_id')
            ->with('leadStatus')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->leadStatus ? $item->leadStatus->name : 'No Status',
                    'color' => $item->leadStatus ? $item->leadStatus->color : '#gray',
                    'total' => $item->total,
                ];
            });

        // Hot leads (high priority next action)
        $hotLeads = (clone $customerQuery)
            ->whereHas('leadStatus', function ($query) {
                $query->where('code', 'hot');
            })
            ->count();

        // Meeting count (action plan contains 'meeting' and date >= today)
        $meetingCount = (clone $customerQuery)
            ->where('next_action_plan', 'ilike', '%meeting%')
            ->whereDate('next_action_date', '>=', now()->toDateString())
            ->count();

        // Dormant leads (no interaction in last 30 days)
        $dormantLeads = (clone $customerQuery)
            ->whereDoesntHave('interactions', function ($query) {
                $query->where('interaction_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();

        // New inbound today (email only)
        $newInboundToday = Interaction::whereDate('interaction_at', Carbon::today())
            ->where('interaction_type', 'email_inbound')
            ->count();

        // Action today
        $actionToday = (clone $customerQuery)
            ->whereDate('next_action_date', Carbon::today())
            ->where('next_action_status', '!=', 'done')
            ->count();

        return response()->json([
            'customers_by_area' => $customersByArea,
            'leads_by_status' => $leadsByStatus,
            'hot_leads' => $hotLeads,
            'meeting_count' => $meetingCount,
            'dormant_leads' => $dormantLeads,
            'new_inbound_today' => $newInboundToday,
            'action_today' => $actionToday,
            'total_customers' => $customerQuery->count(),
        ]);
    }

    public function todayActions(Request $request)
    {
        $userId = $request->user()->id;
        $role = $request->user()->role;

        // Base query - if sales, only their customers
        $query = Customer::query();
        if ($role === 'sales') {
            $query->where('assigned_sales_id', $userId);
        }

        // Filter customers with next_action_date = today
        $customers = $query->whereDate('next_action_date', Carbon::today())
            ->with([
                'area',
                'leadStatus',
                'contacts' => function ($q) {
                    $q->orderBy('is_primary', 'desc');
                },
                'interactions' => function ($q) {
                    $q->orderBy('interaction_at', 'desc')->limit(1);
                }
            ])
            ->orderBy('next_action_date', 'asc')
            ->paginate($request->get('per_page', 10));

        return response()->json($customers);
    }

    public function weekMeetings(Request $request)
    {
        $userId = $request->user()->id;
        $role = $request->user()->role;

        // Base query - if sales, only their customers
        $query = Customer::query();
        if ($role === 'sales') {
            $query->where('assigned_sales_id', $userId);
        }

        // Filter customers with meetings in current week (Monday to Sunday)
        $startOfWeek = Carbon::now()->startOfWeek(); // Monday
        $endOfWeek = Carbon::now()->endOfWeek(); // Sunday

        $customers = $query->where('next_action_plan', 'ilike', '%meeting%')
            ->whereBetween('next_action_date', [$startOfWeek, $endOfWeek])
            ->with([
                'area',
                'leadStatus',
                'contacts' => function ($q) {
                    $q->orderBy('is_primary', 'desc');
                },
                'interactions' => function ($q) {
                    $q->orderBy('interaction_at', 'desc')->limit(1);
                }
            ])
            ->orderBy('next_action_date', 'asc')
            ->paginate($request->get('per_page', 10));

        return response()->json($customers);
    }
}
