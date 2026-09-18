<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_posts' => Post::count(),
            'total_communities' => Community::count(),
            'total_tags' => Tag::count(),
            'total_reports' => \App\Models\Report::count(),
            'posts_today' => Post::whereDate('created_at', today())->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
        ];

        $trendingTags = Tag::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get();

        $recentPosts = Post::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'content' => Str::limit($post->content, 80),
                'author' => $post->user->name ?? 'N/A',
                'type' => $post->type,
                'created_at' => $post->created_at?->diffForHumans(),
            ]);

        $recentUsers = User::latest()
            ->limit(10)
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'status' => $user->status,
                'created_at' => $user->created_at?->diffForHumans(),
            ]);

        $postsByType = Post::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $usersByStatus = User::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'stats' => $stats,
            'trending_tags' => $trendingTags,
            'recent_posts' => $recentPosts,
            'recent_users' => $recentUsers,
            'posts_by_type' => $postsByType,
            'users_by_status' => $usersByStatus,
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->latest()->paginate(15);

        return response()->json($users);
    }

    public function posts(Request $request): JsonResponse
    {
        $query = Post::with('user');

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $posts = $query->latest()->paginate(15);

        return response()->json($posts);
    }

    public function tags(): JsonResponse
    {
        $tags = Tag::withCount('posts')
            ->orderByDesc('posts_count')
            ->paginate(15);

        return response()->json($tags);
    }
}
