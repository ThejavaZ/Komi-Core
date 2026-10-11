<?php

namespace App\Models\Scopes;

use App\Models\UserBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope: los usuarios bloqueados (en cualquier dirección)
 * desaparecen de todas las consultas de Users para el viewer actual.
 *
 * - El propio viewer siempre se ve a sí mismo.
 * - Los global admins ven todos los usuarios (herramientas de moderación).
 * - Sin viewer autenticado (login, queue, consola) no se filtra nada.
 */
class HideBlockedUsersScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $viewer = Auth::user();

        if ($viewer === null || $viewer->is_global_admin) {
            return;
        }

        $builder->where(function ($query) use ($viewer) {
            $query->where('users.id', $viewer->id)
                ->orWhereNotExists(function ($subquery) use ($viewer) {
                    $subquery->selectRaw('1')
                        ->from((new UserBlock)->getTable())
                        ->where(function ($blocks) use ($viewer) {
                            $blocks->whereColumn('user_blocks.blocker_id', 'users.id')
                                ->where('user_blocks.blocked_id', $viewer->id)
                                ->orWhere(function ($reverse) use ($viewer) {
                                    $reverse->whereColumn('user_blocks.blocked_id', 'users.id')
                                        ->where('user_blocks.blocker_id', $viewer->id);
                                });
                        });
                });
        });
    }
}
