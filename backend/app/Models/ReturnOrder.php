<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'order_id',
        'warehouse_id',
        'platform_return_id',
        'return_number',
        'type',
        'status',
        'reason',
        'customer_note',
        'admin_note',
        'refund_amount',
        'currency',
        'items',
        'tracking_number',
        'shipping_carrier',
        'return_date',
        'refund_date',
        'received_date',
        'restocked_date',
    ];

    protected $casts = [
        'items' => 'array',
        'refund_amount' => 'decimal:2',
        'return_date' => 'datetime',
        'refund_date' => 'datetime',
        'received_date' => 'datetime',
        'restocked_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
