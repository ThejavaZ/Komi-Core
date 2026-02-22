<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    protected $fillable  = [
        'id',
        'name',
        'slug',
        'description',
        'icon',
        'banner',
        'owner_id',
        'status',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
