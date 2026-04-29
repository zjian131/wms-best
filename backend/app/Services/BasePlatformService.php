<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;

abstract class BasePlatformService
{
    protected $store;
    protected $apiBaseUrl;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    abstract public function getOrders($startTime = null, $endTime = null);

    abstract public function getReturns($startTime = null, $endTime = null);

    abstract public function getReturnAddress();

    abstract public function refreshToken();

    protected function isTokenExpired()
    {
        if (!$this->store->token_expires_at) {
            return true;
        }

        return $this->store->token_expires_at->isPast();
    }

    protected function makeRequest($method, $endpoint, $data = [])
    {
        if ($this->isTokenExpired()) {
            $this->refreshToken();
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->store->access_token,
            'Accept' => 'application/json',
        ])->$method($this->apiBaseUrl . $endpoint, $data);

        if ($response->failed()) {
            throw new \Exception('API请求失败: ' . $response->body());
        }

        return $response->json();
    }
}
