<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Interaction;
use App\Models\Customer;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function index(Request $request)
    {
        $query = Interaction::with(['customer', 'createdByUser', 'leadStatusSnapshot']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('interaction_type')) {
            $query->where('interaction_type', $request->interaction_type);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        $interactions = $query->orderBy('interaction_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($interactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'interaction_type' => 'required|in:email_inbound,email_outbound,manual_channel,note',
            'channel' => 'nullable|in:email,whatsapp,telephone,instagram,tiktok,tokopedia,shopee,lazada,website_chat,other',
            'subject' => 'nullable|string',
            'content' => 'nullable|string',
            'summary' => 'nullable|string',
            'interaction_at' => 'nullable|date',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        $validated['created_by_type'] = 'user';
        $validated['created_by_user_id'] = auth()->id();
        $validated['lead_status_snapshot_id'] = $customer->lead_status_id;
        $validated['interaction_at'] = $validated['interaction_at'] ?? now();

        $interaction = Interaction::create($validated);

        return response()->json([
            'interaction' => $interaction->load(['customer', 'createdByUser', 'leadStatusSnapshot']),
            'message' => 'Interaction created successfully',
        ], 201);
    }

    public function show($id)
    {
        $interaction = Interaction::with(['customer', 'createdByUser', 'leadStatusSnapshot'])
            ->findOrFail($id);

        return response()->json($interaction);
    }

    public function update(Request $request, $id)
    {
        $interaction = Interaction::findOrFail($id);

        $validated = $request->validate([
            'subject' => 'nullable|string',
            'content' => 'nullable|string',
            'summary' => 'nullable|string',
        ]);

        $interaction->update($validated);

        return response()->json([
            'interaction' => $interaction->load(['customer', 'createdByUser', 'leadStatusSnapshot']),
            'message' => 'Interaction updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $interaction = Interaction::findOrFail($id);
        $interaction->delete();

        return response()->json([
            'message' => 'Interaction deleted successfully',
        ]);
    }
}
