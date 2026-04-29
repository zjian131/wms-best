<?php

namespace App\Services;

use App\Models\Store;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Log;

class SyncService
{
    protected $platformService;

    public function __construct(PlatformService $platformService)
    {
        $this->platformService = $platformService;
    }

    public function syncStore(Store $store, $syncOrders = true, $syncReturns = true)
    {
        $startTime = now();
        $logData = [
            'user_id' => $store->user_id,
            'category' => 'sync',
            'action' => 'sync_start',
            'message' => "开始同步店铺: {$store->store_name}",
            'context' => [
                'store_id' => $store->id,
                'platform' => $store->platform,
            ],
        ];

        SystemLog::create($logData);

        try {
            $lastSync = $store->last_sync_at;
            $ordersCount = 0;
            $returnsCount = 0;

            if ($syncOrders) {
                $ordersCount = $this->platformService->syncStoreOrders($store, $lastSync);
            }

            if ($syncReturns) {
                $returnsCount = $this->platformService->syncStoreReturns($store, $lastSync);
            }

            $duration = $startTime->diffInSeconds(now());

            SystemLog::create([
                'user_id' => $store->user_id,
                'category' => 'sync',
                'action' => 'sync_complete',
                'message' => "店铺同步完成: {$store->store_name} (订单: {$ordersCount}, 退货: {$returnsCount}, 耗时: {$duration}s)",
                'context' => [
                    'store_id' => $store->id,
                    'platform' => $store->platform,
                    'orders_count' => $ordersCount,
                    'returns_count' => $returnsCount,
                    'duration' => $duration,
                ],
            ]);

            return [
                'success' => true,
                'orders_count' => $ordersCount,
                'returns_count' => $returnsCount,
                'duration' => $duration,
            ];
        } catch (\Exception $e) {
            SystemLog::create([
                'user_id' => $store->user_id,
                'level' => 'error',
                'category' => 'sync',
                'action' => 'sync_error',
                'message' => "店铺同步失败: {$store->store_name} - {$e->getMessage()}",
                'context' => [
                    'store_id' => $store->id,
                    'platform' => $store->platform,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncAllStores($userId = null)
    {
        $query = Store::where('is_active', true);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stores = $query->get();
        $results = [];

        foreach ($stores as $store) {
            $results[$store->id] = $this->syncStore($store);
        }

        return $results;
    }
}
