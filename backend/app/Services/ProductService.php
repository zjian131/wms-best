<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\ProductBinding;
use App\Models\SystemLog;

class ProductService
{
    public function bindProductToOrder(Product $product, Order $order, $quantity = 1)
    {
        if ($product->available_stock < $quantity) {
            throw new \Exception('库存不足');
        }

        $binding = ProductBinding::create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'quantity' => $quantity,
            'status' => 'bound',
            'bound_at' => now(),
        ]);

        SystemLog::create([
            'user_id' => $product->user_id,
            'category' => 'product',
            'action' => 'bind_order',
            'message' => "商品 #{$product->id} 绑定到订单 #{$order->id}",
            'context' => [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'quantity' => $quantity,
            ],
        ]);

        return $binding;
    }

    public function unbindProductFromOrder(ProductBinding $binding)
    {
        $product = $binding->product;
        $product->available_stock += $binding->quantity;
        $product->save();

        SystemLog::create([
            'user_id' => $product->user_id,
            'category' => 'product',
            'action' => 'unbind_order',
            'message' => "商品 #{$product->id} 从订单 #{$binding->order_id} 解绑",
            'context' => [
                'product_id' => $product->id,
                'order_id' => $binding->order_id,
                'quantity' => $binding->quantity,
            ],
        ]);

        $binding->delete();

        return true;
    }

    public function updateProductStock(Product $product, $stockQuantity, $availableStock = null)
    {
        $product->stock_quantity = $stockQuantity;
        $product->available_stock = $availableStock ?? $stockQuantity;
        $product->save();

        SystemLog::create([
            'user_id' => $product->user_id,
            'category' => 'product',
            'action' => 'update_stock',
            'message' => "商品 #{$product->id} 库存更新 (总: {$stockQuantity}, 可用: {$product->available_stock})",
            'context' => [
                'product_id' => $product->id,
                'stock_quantity' => $stockQuantity,
                'available_stock' => $product->available_stock,
            ],
        ]);

        return $product;
    }

    public function getAvailableProducts($userId, $filters = [])
    {
        $query = Product::where('user_id', $userId)
            ->where('available_stock', '>', 0)
            ->where('status', 'in_stock');

        if (isset($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['sku'])) {
            $query->where('sku', 'like', '%' . $filters['sku'] . '%');
        }

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 20);
    }

    public function moveProductToWarehouse(Product $product, $warehouseId)
    {
        $oldWarehouseId = $product->warehouse_id;
        $product->warehouse_id = $warehouseId;
        $product->save();

        SystemLog::create([
            'user_id' => $product->user_id,
            'category' => 'product',
            'action' => 'move_warehouse',
            'message' => "商品 #{$product->id} 从仓库 #{$oldWarehouseId} 移动到仓库 #{$warehouseId}",
            'context' => [
                'product_id' => $product->id,
                'old_warehouse_id' => $oldWarehouseId,
                'new_warehouse_id' => $warehouseId,
            ],
        ]);

        return $product;
    }
}
