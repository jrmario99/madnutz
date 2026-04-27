<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'label', 'value', 'price_modifier', 'stock'];

    protected $casts = ['price_modifier' => 'float'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
