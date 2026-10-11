<?php

namespace App\Http\Controllers;

use App\Models\Scopes\HideBlockedUsersScope;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlockController extends Controller
{
    /**
     * El binding `User $user` aplicaría el scope HideBlockedUsersScope y
     * devolveria 404 al intentar desbloquear, por eso se recibe el id crudo
     * y se busca sin el scope.
     */
    public function toggleBlock(Request $request, int $user): JsonResponse
    {
        $target = User::withoutGlobalScope(HideBlockedUsersScope::class)->find($user);

        if (! $target) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if ($request->user()->id === $target->id) {
            return response()->json(['message' => 'No puedes bloquearte a ti mismo.'], 400);
        }

        $isBlocking = $request->user()->isBlocking($target);

        if ($isBlocking) {
            $request->user()->blockedUsers()->detach($target->id);
            $blocked = false;
        } else {
            $request->user()->blockedUsers()->attach($target->id);
            // Si te seguia, dejar de seguir automaticamente
            $request->user()->following()->detach($target->id);
            $blocked = true;
        }

        return response()->json([
            'status' => 'success',
            'blocked' => $blocked,
        ]);
    }

    public function blockedUsers(Request $request): AnonymousResourceCollection
    {
        $blocked = $request->user()
            ->blockedUsers()
            ->withCount('posts')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return \App\Http\Resources\UserResource::collection($blocked);
    }
}
