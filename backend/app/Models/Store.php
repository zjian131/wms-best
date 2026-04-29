<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'store_name',
        'store_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'settings',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function returnOrders()
    {
        return $this->hasMany(ReturnOrder::class);
    }
}
