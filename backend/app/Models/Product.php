<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'warehouse_id',
        'platform_product_id',
        'sku',
        'name',
        'description',
        'image_url',
        'price',
        'currency',
        'stock_quantity',
        'available_stock',
        'return_reason',
        'attributes',
        'status',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function bindings()
    {
        return $this->hasMany(ProductBinding::class);
    }

    public function decreaseStock($quantity = 1)
    {
        if ($this->available_stock >= $quantity) {
            $this->available_stock -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    public function increaseStock($quantity = 1)
    {
        $this->stock_quantity += $quantity;
        $this->available_stock += $quantity;
        $this->save();
        return true;
    }
}
