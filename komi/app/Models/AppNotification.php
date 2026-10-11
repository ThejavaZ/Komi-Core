<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    public const TYPE_LIKE = 'like';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_FOLLOW = 'follow';
    public const TYPE_REPOST = 'repost';

    protected $fillable = [
        'user_id',
        'from_user_id',
        'type',
        'post_id',
        'comment_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Crea una notificacion si el destinatario no es el propio actor.
     */
    public static function notify(
        User $recipient,
        User $actor,
        string $type,
        ?int $postId = null,
        ?int $commentId = null,
    ): ?self {
        if ($recipient->id === $actor->id) {
            return null;
        }

        return self::create([
            'user_id' => $recipient->id,
            'from_user_id' => $actor->id,
            'type' => $type,
            'post_id' => $postId,
            'comment_id' => $commentId,
        ]);
    }
}
