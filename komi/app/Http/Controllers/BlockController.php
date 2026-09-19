<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlockController extends Controller
{
    public function toggleBlock(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes bloquearte a ti mismo.'], 400);
        }

        $isBlocking = $request->user()->isBlocking($user);

        if ($isBlocking) {
            $request->user()->blockedUsers()->detach($user->id);
            $blocked = false;
        } else {
            $request->user()->blockedUsers()->attach($user->id);
            // Si te seguia, dejar de seguir automaticamente
            $request->user()->following()->detach($user->id);
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
