<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\TagResource;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function trending(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->input('limit', 10), 50);
        $window = $request->input('window'); // '7d' | '30d' | null (histórico)
        $communityId = $request->input('community_id');

        // SQLite no permite HAVING sobre el alias de withCount (subquery):
        // pedimos un poco más y filtramos en colección.
        $tags = Tag::query()
            ->withCount(['posts' => function ($query) use ($window, $communityId) {
                if ($window === '7d') {
                    $query->where('posts.created_at', '>=', now()->subDays(7));
                } elseif ($window === '30d') {
                    $query->where('posts.created_at', '>=', now()->subDays(30));
                }
                if ($communityId !== null && $communityId !== '') {
                    $query->where('posts.community_id', (int) $communityId);
                }
            }])
            ->orderByDesc('posts_count')
            ->limit($limit * 2)
            ->get()
            ->filter(fn ($tag) => $tag->posts_count > 0)
            ->take($limit)
            ->values();

        return TagResource::collection($tags);
    }

    public function index(): AnonymousResourceCollection
    {
        return TagResource::collection(Tag::orderBy('name')->get());
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    /**
     * Posts de un hashtag (por slug).
     */
    public function posts(Request $request, string $slug): AnonymousResourceCollection
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();
        $perPage = min((int) $request->input('per_page', 15), 50);

        $posts = $tag->posts()
            ->with(['user', 'community', 'tags'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate($perPage);

        return PostResource::collection($posts);
    }
}
