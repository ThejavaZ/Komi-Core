<?php

namespace App\Support;

use App\Models\Badge;
use App\Models\User;

/**
 * Otorga badges de forma idempotente en los puntos clave de la app
 * (posts, comentarios, likes y creación de comunidades).
 */
class BadgeAwarder
{
    /**
     * Otorga el badge con la clave dada al usuario si aún no lo tiene.
     * No falla nunca: los badges son decorativos y no deben romper el flujo.
     */
    public static function award(User $user, string $key): void
    {
        try {
            $badge = Badge::query()->where('key', $key)->first();
            if ($badge === null) {
                return;
            }

            $user->badges()->syncWithoutDetaching([$badge->id]);
        } catch (\Throwable) {
            // Silencioso: el badge es cosmético.
        }
    }
}
