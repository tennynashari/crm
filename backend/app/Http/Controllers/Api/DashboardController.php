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

        // Warm leads
        $warmLeads = (clone $customerQuery)
            ->whereHas('leadStatus', function ($query) {
                $query->where('code', 'warm');
            })
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
            'warm_leads' => $warmLeads,
            'dormant_leads' => $dormantLeads,
            'new_inbound_today' => $newInboundToday,
            'action_today' => $actionToday,
            'total_customers' => $customerQuery->count(),
        ]);
    }
}
