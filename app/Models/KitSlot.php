<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitSlot extends Model
{
    protected $fillable = ['kit_id', 'size', 'quantity'];

    public function kit()
    {
        return $this->belongsTo(Kit::class);
    }
}
