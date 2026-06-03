<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'events'            => 'array',
        'is_active'         => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /** Hanya webhook yang aktif */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
