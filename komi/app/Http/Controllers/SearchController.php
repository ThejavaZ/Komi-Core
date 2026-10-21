<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommunityResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\UserResource;
use App\Models\Community;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SearchController extends Controller
{
    /**
     * Busqueda unificada: usuarios, posts, comunidades y hashtags en un solo
     * endpoint. Cada categoria limitada a 5 resultados para la UI de tabs.
     *
     * Filtros avanzados (sobre posts): type, from/to (fecha), community_id,
     * author (nombre o username). Ej: /search?q=arta&type=posts&from=2026-10-01
     */
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));

        if ($query === '') {
            return response()->json([
                'users' => [],
                'posts' => [],
                'communities' => [],
                'tags' => [],
            ]);
        }

        $type = $request->input('type');
        $wants = fn (string $key) => $type === null || $type === $key;

        $like = "%{$query}%";
        $limit = min((int) $request->input('per_category', 5), 20);

        $users = [];
        if ($wants('users')) {
            $users = User::query()
                ->where('status', 'active')
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like);
                })
                ->withCount(['followers', 'posts'])
                ->orderBy('followers_count', 'desc')
                ->limit($limit)
                ->get();
        }

        $posts = [];
        if ($wants('posts')) {
            $postsQuery = Post::query()
                ->where('content', 'like', $like)
                ->with(['user', 'community', 'communityCategory', 'tags'])
                ->withCount('comments')
                ->withExists(['reactions as is_liked_by_me' => fn ($q) => $q->where('user_id', $request->user()->id)]);

            if ($request->filled('community_id')) {
                $postsQuery->where('community_id', $request->integer('community_id'));
            }

            if ($request->filled('author')) {
                $authorLike = '%' . $request->string('author') . '%';
                $postsQuery->whereHas('user', function ($q) use ($authorLike) {
                    $q->where(function ($inner) use ($authorLike) {
                        $inner->where('name', 'like', $authorLike)
                            ->orWhere('username', 'like', $authorLike);
                    });
                });
            }

            if ($request->filled('from')) {
                try {
                    $postsQuery->where('created_at', '>=', Carbon::parse($request->input('from'))->startOfDay());
                } catch (\Throwable) {
                    abort(422, 'Fecha "from" inválida. Usa el formato YYYY-MM-DD.');
                }
            }

            if ($request->filled('to')) {
                try {
                    $postsQuery->where('created_at', '<=', Carbon::parse($request->input('to'))->endOfDay());
                } catch (\Throwable) {
                    abort(422, 'Fecha "to" inválida. Usa el formato YYYY-MM-DD.');
                }
            }

            $posts = $postsQuery
                ->latest()
                ->limit($limit)
                ->get();
        }

        $communities = [];
        if ($wants('communities')) {
            $communities = Community::query()
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->withCount(['members', 'posts'])
                ->latest()
                ->limit($limit)
                ->get();
        }

        $tags = [];
        if ($wants('tags')) {
            $tags = Tag::query()
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like);
                })
                ->withCount('posts')
                ->orderByDesc('posts_count')
                ->limit($limit)
                ->get();
        }

        return response()->json([
            'users' => UserResource::collection($users)->resolve(),
            'posts' => PostResource::collection($posts)->resolve(),
            'communities' => CommunityResource::collection($communities)->resolve(),
            'tags' => TagResource::collection($tags)->resolve(),
        ]);
    }
}
