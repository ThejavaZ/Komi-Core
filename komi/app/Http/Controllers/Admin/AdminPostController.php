<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPostController extends Controller
{
    public function trashed(Request $request): JsonResponse
    {
        $posts = Post::with('user')->onlyTrashed()->latest()->paginate(min(max((int) $request->input('per_page', 15), 1), 100));

        return response()->json($posts);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $old = $post->only(['content', 'image_url']);

        $data = $request->validate([
            'content' => ['sometimes', 'string'],
            'image_url' => ['sometimes', 'nullable', 'string'],
        ]);

        $post->update($data);
        AdminLog::log('post.update', $post, $old, $data);

        return response()->json(['post' => $post]);
    }

    public function restore($id): JsonResponse
    {
        $post = Post::withTrashed()->findOrFail($id);
        $post->restore();
        AdminLog::log('post.restore', $post, ['deleted_at' => null], $post->toArray());

        return response()->json([
            'post' => $post,
            'message' => 'Publicación restaurada.',
        ]);
    }
}
