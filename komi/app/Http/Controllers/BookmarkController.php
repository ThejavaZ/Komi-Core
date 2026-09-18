<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BookmarkController extends Controller
{
    /**
     * Toggle bookmark on a post.
     *
     * POST /api/posts/{post}/bookmark
     */
    public function toggle(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()->id;

        $bookmarked = DB::transaction(function () use ($post, $userId): bool {
            $bookmark = Bookmark::query()
                ->where('user_id', $userId)
                ->where('post_id', $post->id)
                ->first();

            if ($bookmark) {
                $bookmark->delete();
                return false;
            }

            Bookmark::create([
                'user_id' => $userId,
                'post_id' => $post->id,
            ]);
            return true;
        });

        return response()->json([
            'status' => 'success',
            'is_bookmarked' => $bookmarked,
        ]);
    }

    /**
     * List the authenticated user's bookmarked posts.
     *
     * GET /api/me/bookmarks
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $bookmarks = Bookmark::query()
            ->where('user_id', $request->user()->id)
            ->with('post')
            ->latest()
            ->paginate($perPage);

        $posts = $bookmarks->getCollection()->map(fn ($b) => $b->post);

        return PostResource::collection(
            $posts->filter()->values()->paginate($perPage)
        );
    }
}
