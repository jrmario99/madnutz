<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kit extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price', 'sale_price', 'image',
        'active', 'featured', 'badge', 'sort_order', 'free_shipping',
    ];

    protected $casts = [
        'price'        => 'float',
        'sale_price'   => 'float',
        'active'       => 'boolean',
        'featured'     => 'boolean',
        'free_shipping' => 'boolean',
    ];

    protected $appends = ['effective_price'];

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->price;
    }

    public function slots()
    {
        return $this->hasMany(KitSlot::class)->orderBy('id');
    }

    public function favorites()
    {
        return $this->morphMany(CustomerFavorite::class, 'favoritable');
    }

    public function images()
    {
        return $this->hasMany(KitImage::class)->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'kit_products')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
