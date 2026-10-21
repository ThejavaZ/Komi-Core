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
            'theme_color' => ['sometimes', 'required', 'string', \Illuminate\Validation\Rule::in(['deepPurple', 'blue', 'emerald', 'crimson', 'amber'])],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Perfil actualizado correctamente.',
            'user' => new UserResource($request->user()->load('badges')->loadCount(['followers', 'following', 'posts'])),
        ]);
    }

    /**
     * Borrado de cuenta self-serve (requisito estricto de Play Store).
     * Anonimiza los datos personales, revoca tokens y soft-deleta el usuario.
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revocamos todos los tokens activos de la cuenta.
        $user->tokens()->delete();

        // Anonimizamos PII conservando la integridad de las FK y la unicidad.
        $user->forceFill([
            'name' => 'Usuario eliminado',
            'username' => 'user_deleted_' . $user->id,
            'email' => 'deleted_' . $user->id . '@komi.invalid',
            'bio' => null,
            'avatar' => null,
            'banner' => null,
            'custom_title' => null,
            'theme_color' => 'deepPurple',
            'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(40)),
        ])->save();

        $user->delete(); // Soft delete: el contenido se conserva pero se oculta.

        return response()->json([
            'status' => 'success',
            'message' => 'Tu cuenta ha sido eliminada.',
        ]);
    }

    public function show(string $username): UserResource
    {
        $user = User::where('username', $username)
            ->withCount(['followers', 'following', 'posts'])
            ->with('badges')
            ->firstOrFail();

        return new UserResource($user);
    }

    /**
     * Estadisticas publicas del usuario: totales, serie de posts
     * de los ultimos 30 dias y top de posts (para la pantalla de graficas).
     */
    public function stats(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $postsPerDay = $user->posts()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn ($post) => $post->created_at->format('Y-m-d'))
            ->map(fn ($group) => $group->count());

        $series = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $series[] = [
                'date' => $date,
                'count' => $postsPerDay[$date] ?? 0,
            ];
        }

        $topPosts = $user->posts()
            ->withCount('comments')
            ->orderByDesc('likes_count')
            ->limit(5)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'content' => \Illuminate\Support\Str::limit($post->content, 120),
                'type' => $post->type,
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
                'created_at' => $post->created_at?->toISOString(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => [
                    'posts' => $user->posts()->count(),
                    'comments' => $user->comments()->count(),
                    'likes_received' => (int) $user->posts()->sum('likes_count'),
                    'followers' => $user->followers()->count(),
                    'following' => $user->following()->count(),
                    'communities' => $user->communities()->count(),
                ],
                'posts_last_30_days' => $series,
                'top_posts' => $topPosts,
            ],
        ]);
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
