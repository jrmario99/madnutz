<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'customer_id', 'number', 'customer_name', 'customer_email', 'customer_phone',
        'customer_address', 'subtotal', 'shipping', 'total',
        'status', 'payment_method', 'payment_ref', 'paid_at', 'notes',
    ];

    protected $casts = [
        'customer_address' => 'array',
        'subtotal'         => 'float',
        'shipping'         => 'float',
        'total'            => 'float',
        'paid_at'          => 'datetime',
    ];

    const STATUSES = ['pending', 'paid', 'shipped', 'cancelled'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->number = 'MN-' . strtoupper(uniqid());
        });
    }
}
