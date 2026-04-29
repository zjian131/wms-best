<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($warehouses);
    }

    public function show($id)
    {
        $warehouse = Warehouse::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($warehouse);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'name' => 'required|string',
            'contact_person' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'country' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'district' => 'nullable|string',
            'address' => 'required|string',
            'postal_code' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($request->is_default) {
            Warehouse::where('user_id', Auth::id())->update(['is_default' => false]);
        }

        $warehouse = Warehouse::create([
            'user_id' => Auth::id(),
            'store_id' => $request->store_id,
            'name' => $request->name,
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'country' => $request->country,
            'province' => $request->province,
            'city' => $request->city,
            'district' => $request->district,
            'address' => $request->address,
            'postal_code' => $request->postal_code,
            'is_default' => $request->is_default ?? false,
            'settings' => $request->settings,
        ]);

        return response()->json($warehouse, 201);
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'name' => 'sometimes|string',
            'contact_person' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'country' => 'sometimes|string',
            'province' => 'sometimes|string',
            'city' => 'sometimes|string',
            'district' => 'nullable|string',
            'address' => 'sometimes|string',
            'postal_code' => 'nullable|string',
            'is_default' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($request->is_default) {
            Warehouse::where('user_id', Auth::id())->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $warehouse->update($request->all());

        return response()->json($warehouse);
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::where('user_id', Auth::id())->findOrFail($id);
        $warehouse->delete();

        return response()->json(null, 204);
    }

    public function setDefault($id)
    {
        $warehouse = Warehouse::where('user_id', Auth::id())->findOrFail($id);

        Warehouse::where('user_id', Auth::id())->update(['is_default' => false]);
        $warehouse->update(['is_default' => true]);

        return response()->json($warehouse);
    }
}
