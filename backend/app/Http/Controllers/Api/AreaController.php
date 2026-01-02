<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::where('is_active', true)->get();
        return response()->json($areas);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:areas',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Generate code from name if not provided
        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 10));
        }

        $area = Area::create($validated);

        return response()->json($area, 201);
    }

    public function show($id)
    {
        $area = Area::findOrFail($id);
        return response()->json($area);
    }

    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:255|unique:areas,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $area->update($validated);

        return response()->json($area);
    }

    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return response()->json([
            'message' => 'Area deleted successfully',
        ], 200);
    }
}
