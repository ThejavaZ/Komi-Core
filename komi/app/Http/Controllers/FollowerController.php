<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FollowerController extends Controller
{
    public function toggleFollow(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes seguirte a ti mismo.'], 400);
        }

        if ($request->user()->isBlocking($user) || $user->isBlocking($request->user())) {
            return response()->json(['message' => 'No puedes seguir a este usuario.'], 403);
        }

        $isFollowing = $request->user()->isFollowing($user);

        if ($isFollowing) {
            $request->user()->following()->detach($user->id);
            $following = false;
        } else {
            $request->user()->following()->attach($user->id);
            $following = true;
        }

        return response()->json([
            'status' => 'success',
            'following' => $following,
            'followers_count' => $user->fresh()->followers()->count(),
        ]);
    }

    public function followers(Request $request, User $user): AnonymousResourceCollection
    {
        $followers = $user->followers()
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return \App\Http\Resources\FollowerResource::collection($followers);
    }

    public function following(Request $request, User $user): AnonymousResourceCollection
    {
        $following = $user->following()
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        return \App\Http\Resources\FollowerResource::collection($following);
    }
}
