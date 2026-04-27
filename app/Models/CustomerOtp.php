<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CustomerOtp extends Model
{
    protected $fillable = ['email', 'code', 'attempts', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('used_at')
                     ->where('expires_at', '>', now())
                     ->where('attempts', '<', 3);
    }

    public function isValid(): bool
    {
        return is_null($this->used_at)
            && $this->expires_at->isFuture()
            && $this->attempts < 3;
    }
}
