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

class SearchController extends Controller
{
    /**
     * Busqueda unificada: usuarios, posts, comunidades y hashtags en un solo
     * endpoint. Cada categoria limitada a 5 resultados para la UI de tabs.
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

        $like = "%{$query}%";
        $limit = min((int) $request->input('per_category', 5), 20);

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

        $posts = Post::query()
            ->where('content', 'like', $like)
            ->with(['user', 'community', 'tags'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->latest()
            ->limit($limit)
            ->get();

        $communities = Community::query()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->withCount(['members', 'posts'])
            ->latest()
            ->limit($limit)
            ->get();

        $tags = Tag::query()
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'users' => UserResource::collection($users)->resolve(),
            'posts' => PostResource::collection($posts)->resolve(),
            'communities' => CommunityResource::collection($communities)->resolve(),
            'tags' => TagResource::collection($tags)->resolve(),
        ]);
    }
}
