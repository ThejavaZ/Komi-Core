<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostListItem extends Model
{
    protected $fillable = [
        'post_id',
        'position',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
