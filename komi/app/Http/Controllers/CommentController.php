<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\CommentReaction;
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
                $query->with(['user', 'replies' => function ($q) use ($request) {
                    $q->with(['user']);
                    $q->withCount('replies');
                    $q->withExists(['likes as is_liked_by_me' => fn ($qq) => $qq->where('user_id', $request->user()->id)]);
                }]);
                $query->withCount('replies');
                $query->withExists(['likes as is_liked_by_me' => fn ($qq) => $qq->where('user_id', $request->user()->id)]);
            }])
            ->withCount('replies')
            ->withExists(['likes as is_liked_by_me' => fn ($qq) => $qq->where('user_id', $request->user()->id)])
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

        if ($request->user()->comments()->count() >= 10) {
            \App\Support\BadgeAwarder::award($request->user(), 'commenter');
        }

        if ($post->user) {
            \App\Models\AppNotification::notify(
                $post->user,
                $request->user(),
                \App\Models\AppNotification::TYPE_COMMENT,
                $post->id,
                $comment->id,
            );
        }

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }

    /**
     * Editar un comentario propio.
     */
    public function update(Request $request, Comment $comment): CommentResource
    {
        if ($comment->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para editar este comentario.');
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment->content = $data['content'];
        $comment->edited_at = now();
        $comment->save();

        $comment->load('user');

        return (new CommentResource($comment))->additional([
            'status' => 'success',
            'message' => 'Comentario actualizado.',
        ]);
    }

    /**
     * Borrar un comentario propio (soft delete).
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        if ($comment->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para eliminar este comentario.');
        }

        DB::transaction(function () use ($comment): void {
            $comment->delete();
            // Sin scopes: el contador debe bajar aunque el post este oculto al viewer.
            \App\Models\Post::withoutGlobalScopes()
                ->whereKey($comment->post_id)
                ->decrement('comments_count');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Comentario eliminado.',
        ]);
    }

    /**
     * Like / unlike a un comentario.
     */
    public function toggleLike(Request $request, Comment $comment): JsonResponse
    {
        $userId = $request->user()->id;

        $liked = DB::transaction(function () use ($comment, $userId): bool {
            $reaction = CommentReaction::query()
                ->where('user_id', $userId)
                ->where('comment_id', $comment->id)
                ->lockForUpdate()
                ->first();

            if ($reaction) {
                $reaction->delete();
                $comment->decrement('likes_count');

                return false;
            }

            CommentReaction::create([
                'user_id' => $userId,
                'comment_id' => $comment->id,
            ]);
            $comment->increment('likes_count');

            return true;
        });

        if ($liked) {
            $comment->loadMissing('user');
            if ($comment->user) {
                \App\Models\AppNotification::notify(
                    $comment->user,
                    $request->user(),
                    \App\Models\AppNotification::TYPE_LIKE,
                    $comment->post_id,
                    $comment->id,
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'liked' => $liked,
            'likes_count' => (int) $comment->refresh()->likes_count,
        ]);
    }
}
