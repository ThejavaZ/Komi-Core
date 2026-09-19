<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Wiki extends Model
{
    protected $fillable = [
        'post_id',
        'title',
        'slug',
        'cover_image',
        'community_id',
        'last_editor_id',
        'version',
    ];

    protected static function booted(): void
    {
        static::creating(function (Wiki $wiki) {
            if (empty($wiki->slug)) {
                $wiki->slug = Str::slug($wiki->title);
            }
        });
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_editor_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WikiVersion::class)->orderByDesc('version');
    }
}
