<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class PlatformService
{
    protected $platforms = [
        'amazon' => AmazonService::class,
        'ebay' => EbayService::class,
        'shopify' => ShopifyService::class,
        'shopee' => ShopeeService::class,
        'lazada' => LazadaService::class,
    ];

    public function getPlatformClass($platform)
    {
        return $this->platforms[$platform] ?? null;
    }

    public function getAvailablePlatforms()
    {
        return [
            'amazon' => [
                'name' => 'Amazon',
                'icon' => 'amazon',
                'oauth_url' => '',
            ],
            'ebay' => [
                'name' => 'eBay',
                'icon' => 'ebay',
                'oauth_url' => '',
            ],
            'shopify' => [
                'name' => 'Shopify',
                'icon' => 'shopify',
                'oauth_url' => '',
            ],
            'shopee' => [
                'name' => 'Shopee',
                'icon' => 'shopee',
                'oauth_url' => '',
            ],
            'lazada' => [
                'name' => 'Lazada',
                'icon' => 'lazada',
                'oauth_url' => '',
            ],
        ];
    }

    public function syncStoreOrders(Store $store, $startTime = null, $endTime = null)
    {
        $platformClass = $this->getPlatformClass($store->platform);

        if (!$platformClass) {
            throw new \Exception('不支持的平台: ' . $store->platform);
        }

        $service = new $platformClass($store);
        $orders = $service->getOrders($startTime, $endTime);

        foreach ($orders as $orderData) {
            $this->importOrder($store, $orderData);
        }

        $store->update(['last_sync_at' => now()]);

        return count($orders);
    }

    public function syncStoreReturns(Store $store, $startTime = null, $endTime = null)
    {
        $platformClass = $this->getPlatformClass($store->platform);

        if (!$platformClass) {
            throw new \Exception('不支持的平台: ' . $store->platform);
        }

        $service = new $platformClass($store);
        $returns = $service->getReturns($startTime, $endTime);

        foreach ($returns as $returnData) {
            $this->importReturn($store, $returnData);
        }

        $store->update(['last_sync_at' => now()]);

        return count($returns);
    }

    protected function importOrder(Store $store, $orderData)
    {
        return Order::updateOrCreate(
            [
                'user_id' => $store->user_id,
                'store_id' => $store->id,
                'platform_order_id' => $orderData['platform_order_id'],
            ],
            [
                'order_number' => $orderData['order_number'],
                'customer_name' => $orderData['customer_name'] ?? null,
                'customer_email' => $orderData['customer_email'] ?? null,
                'customer_phone' => $orderData['customer_phone'] ?? null,
                'total_amount' => $orderData['total_amount'] ?? 0,
                'currency' => $orderData['currency'] ?? 'USD',
                'status' => $orderData['status'],
                'shipping_address' => $orderData['shipping_address'] ?? null,
                'billing_address' => $orderData['billing_address'] ?? null,
                'items' => $orderData['items'] ?? null,
                'order_date' => $orderData['order_date'] ?? null,
                'payment_date' => $orderData['payment_date'] ?? null,
            ]
        );
    }

    protected function importReturn(Store $store, $returnData)
    {
        $order = Order::where('platform_order_id', $returnData['platform_order_id'] ?? null)
            ->where('store_id', $store->id)
            ->first();

        $returnOrder = ReturnOrder::updateOrCreate(
            [
                'user_id' => $store->user_id,
                'store_id' => $store->id,
                'platform_return_id' => $returnData['platform_return_id'],
            ],
            [
                'order_id' => $order->id ?? null,
                'return_number' => $returnData['return_number'],
                'type' => $returnData['type'] ?? 'return',
                'status' => $returnData['status'],
                'reason' => $returnData['reason'] ?? null,
                'customer_note' => $returnData['customer_note'] ?? null,
                'refund_amount' => $returnData['refund_amount'] ?? 0,
                'currency' => $returnData['currency'] ?? 'USD',
                'items' => $returnData['items'] ?? null,
                'tracking_number' => $returnData['tracking_number'] ?? null,
                'shipping_carrier' => $returnData['shipping_carrier'] ?? null,
                'return_date' => $returnData['return_date'] ?? null,
                'refund_date' => $returnData['refund_date'] ?? null,
                'received_date' => $returnData['received_date'] ?? null,
            ]
        );

        if ($returnOrder->status === 'received' && !$returnOrder->restocked_date) {
            $this->restockReturnItems($returnOrder, $store);
        }

        return $returnOrder;
    }

    protected function restockReturnItems(ReturnOrder $returnOrder, Store $store)
    {
        $items = $returnOrder->items ?? [];
        $defaultWarehouse = $store->warehouses()->where('is_default', true)->first();

        foreach ($items as $item) {
            $product = Product::updateOrCreate(
                [
                    'user_id' => $store->user_id,
                    'store_id' => $store->id,
                    'platform_product_id' => $item['product_id'] ?? '',
                    'sku' => $item['sku'] ?? '',
                ],
                [
                    'warehouse_id' => $defaultWarehouse->id ?? null,
                    'name' => $item['name'] ?? 'Unknown Product',
                    'description' => $item['description'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'price' => $item['price'] ?? 0,
                    'currency' => $item['currency'] ?? 'USD',
                    'return_reason' => $returnOrder->reason,
                ]
            );

            $quantity = $item['quantity'] ?? 1;
            $product->stock_quantity += $quantity;
            $product->available_stock += $quantity;
            $product->save();
        }

        $returnOrder->update(['restocked_date' => now()]);
    }

    public function getStoreReturnAddress(Store $store)
    {
        $platformClass = $this->getPlatformClass($store->platform);

        if (!$platformClass) {
            throw new \Exception('不支持的平台: ' . $store->platform);
        }

        $service = new $platformClass($store);
        return $service->getReturnAddress();
    }
}
