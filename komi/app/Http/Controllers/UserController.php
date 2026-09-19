<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => [
                'sometimes', 'required', 'string', 'alpha_dash', 'max:255',
                \Illuminate\Validation\Rule::unique('users', 'username')->ignore($request->user()),
            ],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'avatar' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'banner' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female,unspecified'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil actualizado correctamente.',
            'user' => new UserResource($request->user()->loadCount(['followers', 'following', 'posts'])),
        ]);
    }

    public function show(string $username): UserResource
    {
        $user = User::where('username', $username)
            ->withCount(['followers', 'following', 'posts'])
            ->firstOrFail();

        return new UserResource($user);
    }

    public function userPosts(Request $request, int $id): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $user = User::findOrFail($id);

        $posts = $user->posts()
            ->with(['community', 'tags', 'pollOptions', 'originalPost.user'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 50));

        return \App\Http\Resources\PostResource::collection($posts);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $query = $request->input('q', '');

        $users = User::query()
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('username', 'like', "%{$query}%");
            })
            ->withCount(['followers', 'posts'])
            ->orderBy('followers_count', 'desc')
            ->paginate(min((int) $request->input('per_page', 15), 50));

        return UserResource::collection($users);
    }
}
