<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resuelve el viewer actual de forma segura dentro de los global scopes.
 *
 * Los global scopes se ejecutan mientras el guard de sesión todavía está
 * resolviendo al usuario (SessionGuard::user() no cachea hasta que
 * retrieveById termina). Llamar Auth::user() ahí vuelve a entrar en el
 * guard, que dispara otra query con el mismo scope: recursión infinita,
 * sin que llegue a ejecutarse ninguna query en MySQL, hasta que salta
 * max_execution_time (30s).
 *
 * El flag corta esa reentrada: durante la resolución en curso se devuelve
 * null (la query del propio auth queda sin filtrar, correcto) y la llamada
 * original termina recibiendo al usuario ya cacheado por el guard.
 */
final class ScopeViewer
{
    private static bool $resolving = false;

    public static function get(): ?User
    {
        if (self::$resolving) {
            return null;
        }

        self::$resolving = true;

        try {
            return Auth::user();
        } finally {
            self::$resolving = false;
        }
    }
}
