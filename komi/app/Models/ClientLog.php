<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientLog extends Model
{
    protected $fillable = [
        'error_hash',
        'message',
        'stack_trace',
        'app_version',
        'occurrences_count',
        'last_seen_at',
    ];

    /**
     * Calcula el hash de deduplicación de un error de cliente.
     *
     * md5 de message + stack_trace (normalizados), lo que garantiza que dos
     * reportes equivalentes compartan el mismo error_hash y se agrupen.
     */
    public static function hashFor(string $message, ?string $stackTrace = null): string
    {
        return md5($message.'|'.$stackTrace);
    }

    protected function casts(): array
    {
        return [
            'occurrences_count' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }
}