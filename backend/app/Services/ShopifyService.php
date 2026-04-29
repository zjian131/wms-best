<?php

namespace App\Services;

use App\Models\Store;

class ShopifyService extends BasePlatformService
{
    protected $apiBaseUrl;

    public function __construct(Store $store)
    {
        parent::__construct($store);
        
        $settings = $store->settings ?? [];
        $shopDomain = $settings['shop_domain'] ?? '';
        
        $this->apiBaseUrl = "https://{$shopDomain}/admin/api/2024-01";
    }

    public function getOrders($startTime = null, $endTime = null)
    {
        $orders = [];
        
        try {
            $params = [
                'status' => 'any',
            ];
            
            if ($startTime) {
                $params['created_at_min'] = $startTime->toIso8601String();
            }
            
            if ($endTime) {
                $params['created_at_max'] = $endTime->toIso8601String();
            }
            
            $response = $this->makeRequest('get', '/orders.json', $params);
            
            foreach ($response['orders'] ?? [] as $order) {
                $orders[] = [
                    'platform_order_id' => $order['id'],
                    'order_number' => $order['name'],
                    'customer_name' => $order['customer']['name'] ?? null,
                    'customer_email' => $order['email'] ?? null,
                    'customer_phone' => $order['customer']['phone'] ?? null,
                    'total_amount' => $order['total_price'] ?? 0,
                    'currency' => $order['currency'] ?? 'USD',
                    'status' => $order['financial_status'],
                    'shipping_address' => $order['shipping_address'] ?? null,
                    'billing_address' => $order['billing_address'] ?? null,
                    'order_date' => isset($order['created_at']) ? new \DateTime($order['created_at']) : null,
                    'payment_date' => isset($order['processed_at']) ? new \DateTime($order['processed_at']) : null,
                    'items' => $this->formatOrderItems($order['line_items'] ?? []),
                ];
            }
        } catch (\Exception $e) {
            report($e);
        }
        
        return $orders;
    }

    protected function formatOrderItems($lineItems)
    {
        $items = [];
        
        foreach ($lineItems as $item) {
            $items[] = [
                'product_id' => $item['product_id'] ?? '',
                'variant_id' => $item['variant_id'] ?? '',
                'sku' => $item['sku'] ?? '',
                'name' => $item['title'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'currency' => 'USD',
            ];
        }
        
        return $items;
    }

    public function getReturns($startTime = null, $endTime = null)
    {
        $returns = [];
        
        try {
            $params = [];
            
            if ($startTime) {
                $params['created_at_min'] = $startTime->toIso8601String();
            }
            
            if ($endTime) {
                $params['created_at_max'] = $endTime->toIso8601String();
            }
            
            $response = $this->makeRequest('get', '/returns.json', $params);
            
            foreach ($response['returns'] ?? [] as $return) {
                $returns[] = [
                    'platform_return_id' => $return['id'],
                    'return_number' => $return['name'],
                    'platform_order_id' => $return['order_id'] ?? null,
                    'type' => $return['return_type'] ?? 'return',
                    'status' => $return['status'],
                    'reason' => $return['reason'] ?? null,
                    'customer_note' => $return['note'] ?? null,
                    'refund_amount' => $return['refund_amount'] ?? 0,
                    'currency' => 'USD',
                    'items' => $this->formatReturnItems($return['return_line_items'] ?? []),
                    'return_date' => isset($return['created_at']) ? new \DateTime($return['created_at']) : null,
                    'received_date' => isset($return['delivered_at']) ? new \DateTime($return['delivered_at']) : null,
                ];
            }
        } catch (\Exception $e) {
            report($e);
        }
        
        return $returns;
    }

    protected function formatReturnItems($returnLineItems)
    {
        $items = [];
        
        foreach ($returnLineItems as $item) {
            $items[] = [
                'product_id' => $item['product_id'] ?? '',
                'variant_id' => $item['variant_id'] ?? '',
                'sku' => $item['sku'] ?? '',
                'name' => $item['title'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'currency' => 'USD',
            ];
        }
        
        return $items;
    }

    public function getReturnAddress()
    {
        try {
            $response = $this->makeRequest('get', '/shop.json');
            
            $shop = $response['shop'] ?? [];
            
            return [
                'name' => $shop['name'] ?? 'Default Warehouse',
                'contact_person' => null,
                'phone' => $shop['phone'] ?? null,
                'email' => $shop['email'] ?? null,
                'country' => $shop['country_code'] ?? '',
                'province' => $shop['province_code'] ?? '',
                'city' => $shop['city'] ?? '',
                'district' => '',
                'address' => $shop['address1'] . ($shop['address2'] ? ' ' . $shop['address2'] : ''),
                'postal_code' => $shop['zip'] ?? '',
            ];
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    public function refreshToken()
    {
        try {
            $settings = $this->store->settings ?? [];
            
            $response = Http::post('https://' . $settings['shop_domain'] . '/admin/oauth/access_token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->store->refresh_token,
                'client_id' => $settings['api_key'] ?? '',
                'client_secret' => $settings['api_secret'] ?? '',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                $this->store->update([
                    'access_token' => $data['access_token'],
                    'token_expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : null,
                ]);
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function makeRequest($method, $endpoint, $data = [])
    {
        $settings = $this->store->settings ?? [];
        $apiKey = $settings['api_key'] ?? '';
        $apiSecret = $settings['api_secret'] ?? '';
        
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->store->access_token,
            'Accept' => 'application/json',
        ])->$method($this->apiBaseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new \Exception('API请求失败: ' . $response->body());
        }

        return $response->json();
    }
}
