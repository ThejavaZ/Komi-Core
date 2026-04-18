<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Community_members extends Model
{
    protected $fillable = [
        'community_id',
        'user_id',
        'role',
        'joined_at',
        'status',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function community(){
        return $this->belongsTo(Community::class, 'community_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}