<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImages extends Model
{
    protected $fillable = [
        'post_id',
        'url',
        'position',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
