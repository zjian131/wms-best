<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\PlatformService;
use App\Services\SyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    protected $platformService;
    protected $syncService;

    public function __construct(PlatformService $platformService, SyncService $syncService)
    {
        $this->platformService = $platformService;
        $this->syncService = $syncService;
    }

    public function index()
    {
        $stores = Store::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($stores);
    }

    public function show($id)
    {
        $store = Store::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($store);
    }

    public function store(Request $request)
    {
        $request->validate([
            'platform' => 'required|string',
            'store_name' => 'required|string',
            'store_id' => 'required|string',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'settings' => 'nullable|array',
        ]);

        $store = Store::create([
            'user_id' => Auth::id(),
            'platform' => $request->platform,
            'store_name' => $request->store_name,
            'store_id' => $request->store_id,
            'access_token' => $request->access_token,
            'refresh_token' => $request->refresh_token,
            'settings' => $request->settings,
            'is_active' => true,
        ]);

        if ($store->access_token) {
            try {
                $address = $this->platformService->getStoreReturnAddress($store);

                if ($address) {
                    $store->warehouses()->create([
                        'user_id' => Auth::id(),
                        'name' => $address['name'] ?? $store->store_name . ' Warehouse',
                        'contact_person' => $address['contact_person'] ?? null,
                        'phone' => $address['phone'] ?? null,
                        'email' => $address['email'] ?? null,
                        'country' => $address['country'] ?? '',
                        'province' => $address['province'] ?? '',
                        'city' => $address['city'] ?? '',
                        'district' => $address['district'] ?? '',
                        'address' => $address['address'] ?? '',
                        'postal_code' => $address['postal_code'] ?? '',
                        'is_default' => true,
                    ]);
                }
            } catch (\Exception $e) {
                // 静默处理地址获取失败
            }
        }

        return response()->json($store, 201);
    }

    public function update(Request $request, $id)
    {
        $store = Store::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'store_name' => 'sometimes|string',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $store->update($request->only(['store_name', 'access_token', 'refresh_token', 'settings', 'is_active']));

        return response()->json($store);
    }

    public function destroy($id)
    {
        $store = Store::where('user_id', Auth::id())->findOrFail($id);
        $store->delete();

        return response()->json(null, 204);
    }

    public function sync($id)
    {
        $store = Store::where('user_id', Auth::id())->findOrFail($id);

        $result = $this->syncService->syncStore($store);

        return response()->json($result);
    }

    public function syncAll()
    {
        $results = $this->syncService->syncAllStores(Auth::id());

        return response()->json($results);
    }

    public function platforms()
    {
        $platforms = $this->platformService->getAvailablePlatforms();

        return response()->json($platforms);
    }
}
