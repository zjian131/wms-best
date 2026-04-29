<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'platform_order_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'total_amount',
        'currency',
        'status',
        'shipping_address',
        'billing_address',
        'items',
        'order_date',
        'payment_date',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'items' => 'array',
        'total_amount' => 'decimal:2',
        'order_date' => 'datetime',
        'payment_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function returnOrders()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function productBindings()
    {
        return $this->hasMany(ProductBinding::class);
    }
}
