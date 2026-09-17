<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = Post::query()
            ->with(['user', 'community'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->latest()
            ->paginate(15);

        return PostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request): PostResource
    {
        $data = $request->validated();

        $imageUrl = $data['image_url'] ?? null;

        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('posts', 'public');
        }

        $post = $request->user()->posts()->create([
            'community_id' => $data['community_id'] ?? null,
            'content' => $data['content'],
            'image_url' => $imageUrl,
        ]);

        $post->load(['user', 'community'])->loadCount('comments');
        $post->is_liked_by_me = false;

        return (new PostResource($post))->additional([
            'status' => 'success',
            'message' => 'Publicación creada con éxito.',
        ]);
    }

    /**
     * Remove the resource from storage (soft delete).
     *
     * Solo el propietario del post puede eliminarlo; el registro se conserva
     * (SoftDeletes) y el recurso deja de exponer su contenido y autor.
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403, 'No tienes permiso para eliminar esta publicación.');
        }

        $post->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Publicación eliminada.',
        ]);
    }

    /**
     * Give or remove a like to the specified post atomically.
     */
    public function toggleLike(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()->id;

        $liked = DB::transaction(function () use ($post, $userId): bool {
            $reaction = Reaction::query()
                ->where('user_id', $userId)
                ->where('post_id', $post->id)
                ->lockForUpdate()
                ->first();

            if ($reaction) {
                $reaction->delete();
                $post->decrement('likes_count');

                return false;
            }

            Reaction::create([
                'user_id' => $userId,
                'post_id' => $post->id,
            ]);
            $post->increment('likes_count');

            return true;
        });

        return response()->json([
            'status' => 'success',
            'liked' => $liked,
            'likes_count' => (int) $post->refresh()->likes_count,
        ]);
    }
}
