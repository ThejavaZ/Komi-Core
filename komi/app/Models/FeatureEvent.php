<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Contador de uso de features del cliente (qué se usa y cuánto).
 * Upsert por nombre de feature: solo se incrementa `occurrences_count`.
 */
class FeatureEvent extends Model
{
    protected $fillable = [
        'feature',
        'occurrences_count',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'occurrences_count' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }
}
