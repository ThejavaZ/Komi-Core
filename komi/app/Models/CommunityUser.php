<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityUser extends Model
{
    protected $table = 'community_users';

    protected $fillable = [
        'user_id',
        'community_id',
        'role',
    ];

    protected $casts = [
        'role' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
