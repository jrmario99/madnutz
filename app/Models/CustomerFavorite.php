<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerFavorite extends Model
{
    protected $fillable = ['customer_id', 'favoritable_type', 'favoritable_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }
}
