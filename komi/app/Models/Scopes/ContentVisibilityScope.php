<?php

namespace App\Models\Scopes;

use App\Models\UserBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope aplicado a Posts y Comments:
 *
 * 1. Shadowban: el contenido de un usuario con is_shadowbanned solo es
 *    visible para el propio autor (los demás no reciben error, el post
 *    simplemente no aparece).
 * 2. Bloqueos: el contenido de usuarios bloqueados (en cualquier dirección)
 *    desaparece del feed, comentarios, busquedas y bookmarks.
 *
 * Los global admins ven todo (moderación). Sin viewer autenticado solo se
 * aplica el filtro de shadowban.
 */
class ContentVisibilityScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $viewer = ScopeViewer::get();

        // Los admins ven todo (necesario para moderar contenido shadowbanned).
        if ($viewer !== null && $viewer->is_global_admin) {
            return;
        }

        $table = $model->getTable();

        // 1) Shadowban: visible solo si soy el autor o el autor no está shadowbanned.
        //    withTrashed(): los autores soft-deleted no deben ocultar sus posts del feed.
        $builder->where(function ($query) use ($viewer, $table) {
            if ($viewer !== null) {
                $query->where("{$table}.user_id", $viewer->id)
                    ->orWhereHas('user', function (Builder $userQuery) {
                        $userQuery->withoutGlobalScope(HideBlockedUsersScope::class)
                            ->withTrashed()
                            ->where('is_shadowbanned', false);
                    });
            } else {
                $query->whereHas('user', function (Builder $userQuery) {
                    $userQuery->withoutGlobalScope(HideBlockedUsersScope::class)
                        ->withTrashed()
                        ->where('is_shadowbanned', false);
                });
            }
        });

        // 2) Bloqueos: el autor no debe tener ningun bloqueo con el viewer.
        if ($viewer !== null) {
            $builder->whereDoesntHave('user', function (Builder $userQuery) use ($viewer) {
                $userQuery->withoutGlobalScope(HideBlockedUsersScope::class)
                    ->where(function ($blocks) use ($viewer) {
                        $blocks->whereExists(function ($subquery) use ($viewer) {
                            $subquery->selectRaw('1')
                                ->from((new UserBlock)->getTable())
                                ->whereColumn('user_blocks.blocker_id', 'users.id')
                                ->where('user_blocks.blocked_id', $viewer->id);
                        })->orWhereExists(function ($subquery) use ($viewer) {
                            $subquery->selectRaw('1')
                                ->from((new UserBlock)->getTable())
                                ->whereColumn('user_blocks.blocked_id', 'users.id')
                                ->where('user_blocks.blocker_id', $viewer->id);
                        });
                    });
            });
        }
    }
}
