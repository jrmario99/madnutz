<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitProduct extends Model
{
    protected $fillable = ['kit_id', 'product_id', 'quantity'];

    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
