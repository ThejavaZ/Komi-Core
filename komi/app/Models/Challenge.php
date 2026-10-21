<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Challenge extends Model
{
    protected $fillable = [
        'community_id',
        'user_id',
        'title',
        'description',
        'deadline',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'is_completed' => 'boolean',
        ];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'challenge_participants')
            ->withPivot('joined_at');
    }
}
