<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use Illuminate\Http\Request;

class LeadStatusController extends Controller
{
    public function index()
    {
        $statuses = LeadStatus::where('is_active', true)
            ->orderBy('order')
            ->get();
        return response()->json($statuses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:lead_statuses',
            'color' => 'required|string|max:255',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $status = LeadStatus::create($validated);

        return response()->json([
            'status' => $status,
            'message' => 'Lead status created successfully',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $status = LeadStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:lead_statuses,code,' . $id,
            'color' => 'sometimes|required|string|max:255',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $status->update($validated);

        return response()->json([
            'status' => $status,
            'message' => 'Lead status updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $status = LeadStatus::findOrFail($id);
        $status->delete();

        return response()->json([
            'message' => 'Lead status deleted successfully',
        ]);
    }
}
