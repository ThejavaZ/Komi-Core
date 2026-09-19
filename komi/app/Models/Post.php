<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'community_id',
    'content',
    'type',
    'image_url',
    'likes_count',
    'comments_count',
    'vote_score',
    'reposts_count',
    'original_post_id',
    'edited_at',
])]
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'vote_score' => 'integer',
            'reposts_count' => 'integer',
            'edited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function pollOptions(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function pollVotes(): HasMany
    {
        return $this->hasManyThrough(PollVote::class, PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(self::class, 'original_post_id');
    }

    public function originalPost(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_post_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id');
    }

    // ── Structured content types ───────────────────────────────────────

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function wiki(): HasOne
    {
        return $this->hasOne(Wiki::class);
    }

    public function question(): HasOne
    {
        return $this->hasOne(Question::class);
    }
}
