<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Product;
use App\Models\Order;
use App\Models\ReturnOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $stats = [
            'stores' => Store::where('user_id', $userId)->count(),
            'products' => Product::where('user_id', $userId)->count(),
            'available_products' => Product::where('user_id', $userId)->where('available_stock', '>', 0)->count(),
            'total_orders' => Order::where('user_id', $userId)->count(),
            'pending_returns' => ReturnOrder::where('user_id', $userId)->where('status', 'pending')->count(),
            'total_returns' => ReturnOrder::where('user_id', $userId)->count(),
        ];

        $recentReturns = ReturnOrder::where('user_id', $userId)
            ->with('store')
            ->orderBy('return_date', 'desc')
            ->take(10)
            ->get();

        $recentProducts = Product::where('user_id', $userId)
            ->with('store', 'warehouse')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $stores = Store::where('user_id', $userId)
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_returns' => $recentReturns,
            'recent_products' => $recentProducts,
            'stores' => $stores,
        ]);
    }
}
