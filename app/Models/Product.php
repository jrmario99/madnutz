<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'brand', 'description', 'size',
        'price', 'thumbnail', 'stock', 'active',
    ];

    protected $casts = [
        'price' => 'float',
        'active' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function favorites()
    {
        return $this->morphMany(CustomerFavorite::class, 'favoritable');
    }

    public function kits()
    {
        return $this->belongsToMany(Kit::class, 'kit_products')->withPivot('quantity');
    }
}
