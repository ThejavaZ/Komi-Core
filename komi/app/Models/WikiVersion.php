<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikiVersion extends Model
{
    protected $fillable = [
        'wiki_id',
        'editor_id',
        'version',
        'content',
        'edit_summary',
    ];

    public function wiki(): BelongsTo
    {
        return $this->belongsTo(Wiki::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
