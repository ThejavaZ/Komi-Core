<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreItem extends Model
{
    public const TYPE_TEMPLATE = 'template';
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_ITEM = 'item';

    // Clase de cosmético (si el item es aplicable al perfil/chat).
    public const COSMETIC_FRAME = 'frame';
    public const COSMETIC_CHAT_THEME = 'chat_theme';
    public const COSMETIC_TITLE = 'title';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'title',
        'description',
        'type',
        'cosmetic_type',
        'price',
        'preview_data',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'preview_data' => 'array',
            'price' => 'integer',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(StorePurchase::class, 'item_id');
    }
}
