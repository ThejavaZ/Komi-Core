<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPE_GIF = 'gif';
    public const TYPE_STICKER = 'sticker';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'type',
        'content',
        'media_url',
        'sticker_meta',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sticker_meta' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
