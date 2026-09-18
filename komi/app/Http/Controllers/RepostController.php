<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepostController extends Controller
{
    /**
     * Create a repost of an existing post.
     *
     * POST /api/posts/{post}/repost
     * Body: { "content": "optional comment" }
     */
    public function repost(Request $request, Post $post): JsonResponse
    {
        if ($post->trashed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No puedes republicar una publicación eliminada.',
            ], 422);
        }

        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
        ]);

        $repost = DB::transaction(function () use ($request, $post, $data) {
            $repost = $request->user()->posts()->create([
                'content' => $data['content'] ?? '',
                'type' => 'repost',
                'original_post_id' => $post->id,
            ]);

            $post->increment('reposts_count');

            return $repost;
        });

        $repost->load(['user', 'community', 'originalPost' => function ($q) {
            $q->with(['user', 'pollOptions']);
        }]);

        return response()->json([
            'status' => 'success',
            'message' => 'Publicación republicada.',
            'data' => (new PostResource($repost))->toArray($request),
        ], 201);
    }
}
