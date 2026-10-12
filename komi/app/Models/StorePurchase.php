<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorePurchase extends Model
{
    protected $fillable = [
        'buyer_id',
        'seller_id',
        'item_id',
        'price_paid',
    ];

    protected function casts(): array
    {
        return [
            'price_paid' => 'integer',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StoreItem::class, 'item_id');
    }
}
