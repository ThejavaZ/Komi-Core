<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutoModKeyword extends Model
{
    use HasFactory;

    protected $fillable = ['keyword', 'action'];

    protected function casts(): array
    {
        return [];
    }
}
