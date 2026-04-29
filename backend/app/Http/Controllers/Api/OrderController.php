<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::where('user_id', Auth::id());

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        $orders = $query->orderBy('order_date', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($order);
    }

    public function returns(Request $request)
    {
        $query = ReturnOrder::where('user_id', Auth::id());

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('return_date', [$request->start_date, $request->end_date]);
        }

        $returns = $query->orderBy('return_date', 'desc')->paginate($request->per_page ?? 20);

        return response()->json($returns);
    }

    public function showReturn($id)
    {
        $return = ReturnOrder::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($return);
    }

    public function updateReturn(Request $request, $id)
    {
        $return = ReturnOrder::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'status' => 'sometimes|string',
            'admin_note' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'received_date' => 'nullable|date',
            'restocked_date' => 'nullable|date',
        ]);

        $return->update($request->all());

        return response()->json($return);
    }

    public function markAsReceived($id)
    {
        $return = ReturnOrder::where('user_id', Auth::id())->findOrFail($id);

        $return->update([
            'status' => 'received',
            'received_date' => now(),
        ]);

        return response()->json($return);
    }

    public function markAsRestocked($id)
    {
        $return = ReturnOrder::where('user_id', Auth::id())->findOrFail($id);

        $return->update([
            'status' => 'restocked',
            'restocked_date' => now(),
        ]);

        return response()->json($return);
    }
}
