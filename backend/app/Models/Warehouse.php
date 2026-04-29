<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'country',
        'province',
        'city',
        'district',
        'address',
        'postal_code',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function returnOrders()
    {
        return $this->hasMany(ReturnOrder::class);
    }

    public function getFullAddressAttribute()
    {
        $parts = [
            $this->country,
            $this->province,
            $this->city,
            $this->district,
            $this->address,
        ];
        return implode(' ', array_filter($parts));
    }
}
