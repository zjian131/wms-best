<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['store_id', 'warehouse_id', 'sku', 'name', 'per_page']);
        $products = $this->productService->getAvailableProducts(Auth::id(), $filters);

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'platform_product_id' => 'required|string',
            'sku' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'price' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'return_reason' => 'nullable|string',
        ]);

        $product = Product::create([
            'user_id' => Auth::id(),
            'store_id' => $request->store_id,
            'warehouse_id' => $request->warehouse_id,
            'platform_product_id' => $request->platform_product_id,
            'sku' => $request->sku,
            'name' => $request->name,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'price' => $request->price ?? 0,
            'currency' => $request->currency ?? 'USD',
            'stock_quantity' => $request->stock_quantity ?? 0,
            'available_stock' => $request->stock_quantity ?? 0,
            'attributes' => $request->attributes,
            'return_reason' => $request->return_reason,
            'status' => 'in_stock',
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'name' => 'sometimes|string',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url',
            'price' => 'nullable|numeric',
            'currency' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'return_reason' => 'nullable|string',
            'status' => 'sometimes|string',
        ]);

        $product->update($request->all());

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }

    public function bindOrder(Request $request, $id)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'quantity' => 'nullable|integer|min:1|max:' . $product->available_stock,
        ]);

        $order = Order::where('user_id', Auth::id())->findOrFail($request->order_id);
        $quantity = $request->quantity ?? 1;

        $binding = $this->productService->bindProductToOrder($product, $order, $quantity);

        return response()->json($binding, 201);
    }

    public function updateStock(Request $request, $id)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'available_stock' => 'nullable|integer|min:0',
        ]);

        $product = $this->productService->updateProductStock(
            $product,
            $request->stock_quantity,
            $request->available_stock
        );

        return response()->json($product);
    }

    public function moveWarehouse(Request $request, $id)
    {
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $product = $this->productService->moveProductToWarehouse($product, $request->warehouse_id);

        return response()->json($product);
    }
}
