<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * Display the comments of the given post with their author loaded.
     */
    public function index(Request $request, Post $post): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $comments = $post->comments()
            ->with(['user', 'replies' => function ($query) use ($request) {
                $query->with(['user', 'replies' => function ($q) {
                    $q->with(['user']);
                    $q->withCount('replies');
                }]);
                $query->withCount('replies');
            }])
            ->withCount('replies')
            ->whereNull('parent_id')
            ->latest()
            ->paginate($perPage);

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created comment for the given post.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $parentId = $request->validated('parent_id');

        if ($parentId !== null && ! Comment::whereKey($parentId)->where('post_id', $post->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'El comentario padre no pertenece a esta publicación.',
            ], 422);
        }

        $comment = DB::transaction(function () use ($request, $post, $parentId): Comment {
            $comment = Comment::create([
                'user_id' => $request->user()->id,
                'post_id' => $post->id,
                'parent_id' => $parentId,
                'content' => $request->validated('content'),
            ]);

            $post->increment('comments_count');

            return $comment;
        });

        $comment->load('user');

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }
}
