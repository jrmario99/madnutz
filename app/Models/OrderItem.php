<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'kit_id', 'kit_name_snapshot', 'price_snapshot',
        'quantity', 'is_custom', 'custom_items',
    ];

    protected $casts = [
        'price_snapshot' => 'float',
        'is_custom'      => 'boolean',
        'custom_items'   => 'array',
    ];

    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
