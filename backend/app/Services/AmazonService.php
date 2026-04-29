<?php

namespace App\Services;

use App\Models\Store;

class AmazonService extends BasePlatformService
{
    protected $apiBaseUrl = 'https://sellingpartnerapi.amazon.com';

    public function __construct(Store $store)
    {
        parent::__construct($store);
        
        $settings = $store->settings ?? [];
        $region = $settings['region'] ?? 'us';
        
        $regionUrls = [
            'us' => 'https://sellingpartnerapi-na.amazon.com',
            'eu' => 'https://sellingpartnerapi-eu.amazon.com',
            'fe' => 'https://sellingpartnerapi-fe.amazon.com',
        ];
        
        $this->apiBaseUrl = $regionUrls[$region] ?? $regionUrls['us'];
    }

    public function getOrders($startTime = null, $endTime = null)
    {
        $orders = [];
        
        try {
            $params = [
                'MarketplaceIds' => $this->store->settings['marketplace_ids'] ?? ['ATVPDKIKX0DER'],
            ];
            
            if ($startTime) {
                $params['CreatedAfter'] = $startTime->toIso8601ZuluString();
            }
            
            if ($endTime) {
                $params['CreatedBefore'] = $endTime->toIso8601ZuluString();
            }
            
            $response = $this->makeRequest('get', '/orders/v0/orders', $params);
            
            foreach ($response['Orders'] ?? [] as $order) {
                $orders[] = [
                    'platform_order_id' => $order['AmazonOrderId'],
                    'order_number' => $order['AmazonOrderId'],
                    'customer_name' => $order['BuyerInfo']['BuyerName'] ?? null,
                    'customer_email' => $order['BuyerInfo']['BuyerEmail'] ?? null,
                    'total_amount' => $order['OrderTotal']['Amount'] ?? 0,
                    'currency' => $order['OrderTotal']['CurrencyCode'] ?? 'USD',
                    'status' => $order['OrderStatus'],
                    'shipping_address' => $order['ShippingAddress'] ?? null,
                    'order_date' => isset($order['PurchaseDate']) ? new \DateTime($order['PurchaseDate']) : null,
                    'payment_date' => isset($order['PaymentDate']) ? new \DateTime($order['PaymentDate']) : null,
                    'items' => $this->getOrderItems($order['AmazonOrderId']),
                ];
            }
        } catch (\Exception $e) {
            report($e);
        }
        
        return $orders;
    }

    protected function getOrderItems($orderId)
    {
        try {
            $response = $this->makeRequest('get', "/orders/v0/orders/{$orderId}/orderItems");
            
            $items = [];
            foreach ($response['OrderItems'] ?? [] as $item) {
                $items[] = [
                    'product_id' => $item['ASIN'],
                    'sku' => $item['SellerSKU'] ?? '',
                    'name' => $item['Title'] ?? '',
                    'quantity' => $item['QuantityOrdered'] ?? 1,
                    'price' => $item['ItemPrice']['Amount'] ?? 0,
                    'currency' => $item['ItemPrice']['CurrencyCode'] ?? 'USD',
                ];
            }
            
            return $items;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getReturns($startTime = null, $endTime = null)
    {
        $returns = [];
        
        try {
            $params = [];
            
            if ($startTime) {
                $params['CreatedAfter'] = $startTime->toIso8601ZuluString();
            }
            
            if ($endTime) {
                $params['CreatedBefore'] = $endTime->toIso8601ZuluString();
            }
            
            $response = $this->makeRequest('get', '/orders/v0/returns', $params);
            
            foreach ($response['ReturnOrders'] ?? [] as $return) {
                $returns[] = [
                    'platform_return_id' => $return['ReturnOrderId'],
                    'return_number' => $return['ReturnOrderId'],
                    'platform_order_id' => $return['AmazonOrderId'] ?? null,
                    'type' => 'return',
                    'status' => $return['Status'],
                    'reason' => $return['ReturnReason'] ?? null,
                    'refund_amount' => $return['ReturnAmount']['Amount'] ?? 0,
                    'currency' => $return['ReturnAmount']['CurrencyCode'] ?? 'USD',
                    'items' => $return['Items'] ?? [],
                    'return_date' => isset($return['ReturnDate']) ? new \DateTime($return['ReturnDate']) : null,
                    'received_date' => isset($return['ReceivedDate']) ? new \DateTime($return['ReceivedDate']) : null,
                ];
            }
        } catch (\Exception $e) {
            report($e);
        }
        
        return $returns;
    }

    public function getReturnAddress()
    {
        try {
            $response = $this->makeRequest('get', '/fba/inbound/v0/addresses');
            
            $address = $response['Addresses'][0] ?? [];
            
            return [
                'name' => 'Default Warehouse',
                'contact_person' => $address['Name'] ?? null,
                'phone' => null,
                'email' => null,
                'country' => $address['CountryCode'] ?? '',
                'province' => $address['StateOrProvinceCode'] ?? '',
                'city' => $address['City'] ?? '',
                'district' => '',
                'address' => $address['AddressLine1'] . ($address['AddressLine2'] ? ' ' . $address['AddressLine2'] : ''),
                'postal_code' => $address['PostalCode'] ?? '',
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
            
            $response = Http::post('https://api.amazon.com/auth/o2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->store->refresh_token,
                'client_id' => $settings['client_id'] ?? '',
                'client_secret' => $settings['client_secret'] ?? '',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                $this->store->update([
                    'access_token' => $data['access_token'],
                    'token_expires_at' => now()->addSeconds($data['expires_in']),
                ]);
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
