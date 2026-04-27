<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitImage extends Model
{
    protected $fillable = ['kit_id', 'url', 'sort_order'];

    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }
}
