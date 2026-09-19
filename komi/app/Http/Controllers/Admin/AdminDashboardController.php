<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\AdminSetting;
use App\Models\Appeal;
use App\Models\ClientLog;
use App\Models\Community;
use App\Models\Post;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    private function applySort(Request $request, $query, array $allowed, string $default = 'created_at'): void
    {
        $sort = $request->input('sort', $default);
        $dir = $request->input('direction', 'desc');
        if (!in_array($sort, $allowed)) $sort = $default;
        $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
    }

    private function perPage(Request $request, int $default = 15): int
    {
        return min(max((int) $request->input('per_page', $default), 1), 100);
    }

    // ─── Dashboard ──────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_posts' => Post::count(),
            'total_communities' => Community::count(),
            'total_tags' => Tag::count(),
            'total_reports' => Report::count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
            'posts_today' => Post::whereDate('created_at', today())->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'total_comments' => \App\Models\Comment::count(),
            'total_reactions' => \App\Models\Reaction::count(),
            'pending_appeals' => Appeal::where('status', 'pending')->count(),
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

        $postsPerDay = Post::where('created_at', '>=', now()->subDays(14))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $usersPerDay = User::where('created_at', '>=', now()->subDays(14))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $reactionsPerDay = \App\Models\Reaction::where('created_at', '>=', now()->subDays(14))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $activeCommunities = Community::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(5)
            ->get();

        $topPosts = Post::with('user')
            ->orderByDesc('likes_count')
            ->limit(5)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'content' => Str::limit($post->content, 60),
                'author' => $post->user->name ?? 'N/A',
                'likes_count' => $post->likes_count,
                'comments_count' => $post->comments_count,
            ]);

        return response()->json([
            'stats' => $stats,
            'trending_tags' => $trendingTags,
            'recent_posts' => $recentPosts,
            'recent_users' => $recentUsers,
            'posts_by_type' => $postsByType,
            'users_by_status' => $usersByStatus,
            'posts_per_day' => $postsPerDay,
            'users_per_day' => $usersPerDay,
            'reactions_per_day' => $reactionsPerDay,
            'active_communities' => $activeCommunities,
            'top_posts' => $topPosts,
        ]);
    }

    // ─── Users ──────────────────────────────────────────────────
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

        $this->applySort($request, $query, ['name', 'username', 'email', 'status', 'created_at']);
        $users = $query->paginate($this->perPage($request));

        return response()->json($users);
    }

    public function showUser($id): JsonResponse
    {
        $user = User::findOrFail($id);

        $postsCount = $user->posts()->count();
        $commentsCount = $user->comments()->count();
        $reactionsCount = $user->reactions()->count();
        $recentPosts = $user->posts()->latest()->limit(10)->get()->map(fn ($post) => [
            'id' => $post->id,
            'content' => Str::limit($post->content, 80),
            'type' => $post->type,
            'likes_count' => $post->likes_count,
            'comments_count' => $post->comments_count,
            'created_at' => $post->created_at?->diffForHumans(),
        ]);

        $communities = $user->communities()->withCount('posts')->get();

        $reportsAgainst = Report::where('reportable_type', 'App\Models\Post')
            ->whereIn('reportable_id', $user->posts()->pluck('id'))
            ->with('reporter')
            ->latest()
            ->limit(20)
            ->get()
            ->merge(
                Report::where('reportable_type', 'App\Models\Comment')
                    ->whereIn('reportable_id', $user->comments()->pluck('id'))
                    ->with('reporter')
                    ->latest()
                    ->limit(20)
                    ->get()
            )
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'user' => $user,
            'stats' => [
                'posts' => $postsCount,
                'comments' => $commentsCount,
                'reactions' => $reactionsCount,
            ],
            'recent_posts' => $recentPosts,
            'communities' => $communities,
            'reports_against' => $reportsAgainst,
        ]);
    }

    public function updateUser(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $old = $user->only(['status', 'is_global_admin', 'is_verified']);

        $data = $request->validate([
            'status' => ['sometimes', 'string', 'in:active,suspended,banned'],
            'is_global_admin' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);
        AdminLog::log('user.update', $user, $old, $data);

        return response()->json(['user' => $user]);
    }

    public function warnUser($id): JsonResponse
    {
        $user = User::findOrFail($id);
        $old = ['warnings_count' => $user->warnings_count];
        $user->increment('warnings_count');
        AdminLog::log('user.warn', $user, $old, ['warnings_count' => $user->warnings_count]);

        return response()->json(['user' => $user]);
    }

    public function bulkUsers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'action' => ['required', 'string', 'in:activate,suspend,ban,verify'],
        ]);

        $users = User::whereIn('id', $data['ids'])->get();

        foreach ($users as $user) {
            $old = $user->only(['status', 'is_verified']);
            match ($data['action']) {
                'activate' => $user->update(['status' => 'active']),
                'suspend' => $user->update(['status' => 'suspended']),
                'ban' => $user->update(['status' => 'banned']),
                'verify' => $user->update(['is_verified' => true]),
            };
            AdminLog::log('user.bulk.' . $data['action'], $user, $old, $user->only(['status', 'is_verified']));
        }

        return response()->json(['message' => count($users) . ' usuarios actualizados.']);
    }

    // ─── Posts ──────────────────────────────────────────────────
    public function posts(Request $request): JsonResponse
    {
        $query = Post::with('user');

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('content', 'like', "%{$search}%");
        }

        $posts = $query->latest()->paginate($this->perPage($request));

        return response()->json($posts);
    }

    public function destroyPost($id): JsonResponse
    {
        $post = Post::findOrFail($id);
        AdminLog::log('post.delete', $post, $post->toArray());
        $post->delete();

        return response()->json(['message' => 'Publicación eliminada.']);
    }

    public function bulkPosts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'action' => ['required', 'string', 'in:delete'],
        ]);

        $posts = Post::whereIn('id', $data['ids'])->get();

        foreach ($posts as $post) {
            AdminLog::log('post.bulk.delete', $post, $post->toArray());
            $post->delete();
        }

        return response()->json(['message' => count($posts) . ' posts eliminados.']);
    }

    // ─── Tags ───────────────────────────────────────────────────
    public function tags(): JsonResponse
    {
        $tags = Tag::withCount('posts')
            ->orderByDesc('posts_count')
            ->paginate($this->perPage($request));

        return response()->json($tags);
    }

    // ─── Reports / Moderation ───────────────────────────────────
    public function reports(Request $request): JsonResponse
    {
        $query = Report::with('reporter');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $reports = $query->latest()->paginate($this->perPage($request));

        return response()->json($reports);
    }

    public function moderationQueue(Request $request): JsonResponse
    {
        $query = Report::with(['reporter', 'reportable', 'reportable.user']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'pending');
        }

        if ($request->has('type')) {
            $query->where('reportable_type', $request->input('type'));
        }

        $reports = $query->latest()->paginate($this->perPage($request));

        return response()->json($reports);
    }

    public function resolveReport(Request $request, $id): JsonResponse
    {
        $report = Report::findOrFail($id);
        $old = $report->only(['status', 'admin_notes']);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:resolved,dismissed'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $report->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        AdminLog::log('report.resolve', $report, $old, $data);

        return response()->json(['report' => $report]);
    }

    public function moderateContent(Request $request, $reportId): JsonResponse
    {
        $report = Report::findOrFail($reportId);

        $data = $request->validate([
            'action' => ['required', 'string', 'in:delete_post,warn_user,ban_user,dismiss'],
        ]);

        $target = $report->reportable;

        match ($data['action']) {
            'delete_post' => $target?->delete(),
            'warn_user' => $target?->user?->increment('warnings_count'),
            'ban_user' => $target?->user?->update(['status' => 'banned']),
        };

        $report->update(['status' => 'resolved']);
        AdminLog::log('moderation.' . $data['action'], $report, null, ['report_id' => $report->id]);

        return response()->json(['message' => 'Acción aplicada.']);
    }

    // ─── Communities ────────────────────────────────────────────
    public function communities(): JsonResponse
    {
        $communities = Community::withCount(['posts', 'members'])
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json($communities);
    }

    public function destroyCommunity($id): JsonResponse
    {
        $community = Community::findOrFail($id);
        AdminLog::log('community.delete', $community, $community->toArray());
        $community->delete();

        return response()->json(['message' => 'Comunidad eliminada.']);
    }

    // ─── Appeals ────────────────────────────────────────────────
    public function appeals(Request $request): JsonResponse
    {
        $query = Appeal::with(['user', 'reviewer']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $appeals = $query->latest()->paginate($this->perPage($request));

        return response()->json($appeals);
    }

    public function resolveAppeal(Request $request, $id): JsonResponse
    {
        $appeal = Appeal::findOrFail($id);
        $old = $appeal->only(['status', 'admin_notes']);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $appeal->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_by' => auth()->id(),
        ]);

        if ($data['status'] === 'approved') {
            $appeal->user->update(['status' => 'active']);
        }

        AdminLog::log('appeal.resolve', $appeal, $old, $data);

        return response()->json(['appeal' => $appeal]);
    }

    // ─── Admin Logs ─────────────────────────────────────────────
    public function logs(Request $request): JsonResponse
    {
        $query = AdminLog::with('admin');

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        $logs = $query->latest()->paginate($this->perPage($request, 20));

        return response()->json($logs);
    }

    // ─── System Health ──────────────────────────────────────────
    public function systemHealth(): JsonResponse
    {
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (\Exception) {
            $dbOk = false;
        }

        $jobsPending = DB::table('jobs')->count();
        $cacheStats = [
            'keys' => DB::table('cache')->count(),
        ];

        $diskFree = @disk_free_bytes(storage_path()) ?: 0;
        $diskTotal = @disk_total_bytes(storage_path()) ?: 1;

        return response()->json([
            'database' => $dbOk ? 'ok' : 'error',
            'cache_keys' => $cacheStats['keys'],
            'jobs_pending' => $jobsPending,
            'disk_free_gb' => round($diskFree / 1073741824, 2),
            'disk_total_gb' => round($diskTotal / 1073741824, 2),
            'disk_used_percent' => round((1 - $diskFree / $diskTotal) * 100, 1),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_env' => config('app.env'),
        ]);
    }

    public function systemErrors(Request $request): JsonResponse
    {
        $query = ClientLog::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('message', 'like', "%{$search}%");
        }

        $errors = $query->latest('last_seen_at')->paginate($this->perPage($request, 20));

        return response()->json($errors);
    }

    // ─── Jobs Queue ────────────────────────────────────────────
    public function jobs(): JsonResponse
    {
        $jobs = DB::table('jobs')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'display_name' => $payload['displayName'] ?? 'N/A',
                    'job_class' => class_basename($payload['job'] ?? 'N/A'),
                    'attempts' => $job->attempts,
                    'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                    'created_at' => date('Y-m-d H:i:s', $job->created_at),
                ];
            });

        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue ?? 'N/A',
                    'display_name' => $job->display_name ?? 'N/A',
                    'exception' => Str::limit($job->exception, 200),
                    'failed_at' => $job->failed_at,
                ];
            });

        return response()->json([
            'jobs' => $jobs,
            'failed_jobs' => $failedJobs,
            'pending_count' => DB::table('jobs')->count(),
            'failed_count' => DB::table('failed_jobs')->count(),
        ]);
    }

    public function retryJob($id): JsonResponse
    {
        $failedJob = DB::table('failed_jobs')->where('id', $id)->first();
        if (!$failedJob) {
            return response()->json(['message' => 'Job no encontrado.'], 404);
        }

        $job = (unserialize($failedJob->payload));
        // For now, just delete the failed job record
        DB::table('failed_jobs')->where('id', $id)->delete();

        AdminLog::log('system.job.retry', null, null, ['job_id' => $id, 'display_name' => $failedJob->display_name]);
        return response()->json(['message' => 'Job eliminado de la cola de fallidos.']);
    }

    public function deleteJob($id): JsonResponse
    {
        DB::table('failed_jobs')->where('id', $id)->delete();
        AdminLog::log('system.job.delete', null, null, ['job_id' => $id]);
        return response()->json(['message' => 'Job eliminado.']);
    }

    public function clearJobs(): JsonResponse
    {
        $count = DB::table('failed_jobs')->count();
        DB::table('failed_jobs')->truncate();
        AdminLog::log('system.jobs.clear', null, null, ['count' => $count]);
        return response()->json(['message' => "{$count} jobs fallidos eliminados."]);
    }

    // ─── Cache Management ──────────────────────────────────────
    public function clearCache(): JsonResponse
    {
        Cache::flush();
        Artisan::call('cache:clear');
        AdminLog::log('system.cache.clear', null, null);
        return response()->json(['message' => 'Caché limpiada correctamente.']);
    }

    // ─── Enhanced Analytics ────────────────────────────────────
    public function analytics(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 14);
        $since = now()->subDays($days);
        $communityId = $request->input('community_id');
        $compare = $request->boolean('compare', false);

        $postQuery = Post::where('created_at', '>=', $since);
        $userQuery = User::where('created_at', '>=', $since);
        $reactionQuery = \App\Models\Reaction::where('created_at', '>=', $since);

        if ($communityId) {
            $postQuery->where('community_id', $communityId);
        }

        $postsPerDay = (clone $postQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $usersPerDay = (clone $userQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $reactionsPerDay = (clone $reactionQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $postsByCommunity = Community::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get()
            ->pluck('posts_count', 'name');

        $reportsByReason = Report::select('reason', DB::raw('count(*) as total'))
            ->groupBy('reason')
            ->orderByDesc('total')
            ->pluck('total', 'reason');

        $activityByHour = (clone $postQuery)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour');

        $userGrowthWeekly = (clone $userQuery)
            ->select(
                DB::raw('YEARWEEK(created_at, 1) as week'),
                DB::raw('count(*) as total')
            )
            ->groupBy('week')
            ->orderBy('week')
            ->get();

        // Engagement rate: (likes + comments) per day
        $engagementPerDay = Post::where('created_at', '>=', $since)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(likes_count + comments_count) as total_engagement'),
                DB::raw('COUNT(*) as total_posts')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => $row->total_posts > 0 ? round($row->total_engagement / $row->total_posts, 2) : 0,
            ]);

        // Compare with previous period
        $comparison = null;
        if ($compare) {
            $prevSince = $since->copy()->subDays($days);
            $prevUntil = $since->copy()->subDay();

            $prevPosts = Post::whereBetween('created_at', [$prevSince, $prevUntil])->count();
            $currPosts = (clone $postQuery)->count();
            $prevUsers = User::whereBetween('created_at', [$prevSince, $prevUntil])->count();
            $currUsers = (clone $userQuery)->count();
            $prevReactions = \App\Models\Reaction::whereBetween('created_at', [$prevSince, $prevUntil])->count();
            $currReactions = (clone $reactionQuery)->count();

            $comparison = [
                'posts' => ['current' => $currPosts, 'previous' => $prevPosts, 'change' => $prevPosts > 0 ? round(($currPosts - $prevPosts) / $prevPosts * 100, 1) : 0],
                'users' => ['current' => $currUsers, 'previous' => $prevUsers, 'change' => $prevUsers > 0 ? round(($currUsers - $prevUsers) / $prevUsers * 100, 1) : 0],
                'reactions' => ['current' => $currReactions, 'previous' => $prevReactions, 'change' => $prevReactions > 0 ? round(($currReactions - $prevReactions) / $prevReactions * 100, 1) : 0],
            ];
        }

        return response()->json([
            'posts_per_day' => $postsPerDay,
            'users_per_day' => $usersPerDay,
            'reactions_per_day' => $reactionsPerDay,
            'posts_by_community' => $postsByCommunity,
            'reports_by_reason' => $reportsByReason,
            'activity_by_hour' => $activityByHour,
            'user_growth_weekly' => $userGrowthWeekly,
            'engagement_per_day' => $engagementPerDay,
            'comparison' => $comparison,
        ]);
    }
}
