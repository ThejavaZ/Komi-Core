<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Report::with(['reporter', 'reportable', 'reportable.user']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('reportable_type', $request->input('type'));
        }

        if ($request->has('user_id')) {
            $query->where('reportable.user_id', $request->input('user_id'));
        }

        if ($request->has('reporter_id')) {
            $query->where('user_id', $request->input('reporter_id'));
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        if ($request->has('min_reports')) {
            $minReports = (int) $request->input('min_reports');
            $query->whereIn('reportable_id', function ($q) use ($minReports, $request) {
                $q->select('reportable_id')
                    ->from('reports')
                    ->where('reportable_type', $request->input('type', 'App\Models\Post'))
                    ->groupBy('reportable_id')
                    ->havingRaw('COUNT(*) >= ?', [$minReports]);
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $reports = $query->latest()->paginate($perPage);

        return response()->json($reports);
    }

    public function showUserHistory($userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $reportsReceived = Report::where('reportable_type', 'App\Models\Post')
            ->whereIn('reportable_id', $user->posts()->pluck('id'))
            ->with('reporter')
            ->latest()
            ->get()
            ->merge(
                Report::where('reportable_type', 'App\Models\Comment')
                    ->whereIn('reportable_id', $user->comments()->pluck('id'))
                    ->with('reporter')
                    ->latest()
                    ->get()
            )
            ->sortByDesc('created_at')
            ->values();

        $reportsFiled = $user->reports()->with('reportable')->latest()->get();

        $moderationActions = AdminLog::where('target_type', User::class)
            ->where('target_id', $userId)
            ->with('admin')
            ->latest()
            ->get();

        return response()->json([
            'user' => $user,
            'reports_received' => $reportsReceived,
            'reports_filed' => $reportsFiled,
            'moderation_actions' => $moderationActions,
        ]);
    }

    public function toggleShadowban($userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $old = ['is_shadowbanned' => $user->is_shadowbanned];
        $user->update(['is_shadowbanned' => !$user->is_shadowbanned]);
        AdminLog::log('user.shadowban.toggle', $user, $old, ['is_shadowbanned' => $user->is_shadowbanned]);

        return response()->json([
            'user' => $user,
            'message' => $user->is_shadowbanned ? 'Shadowban activado.' : 'Shadowban desactivado.',
        ]);
    }

    public function tempBan(Request $request, $userId): JsonResponse
    {
        $data = $request->validate([
            'duration' => ['required', 'string', 'in:24h,7d,30d,custom'],
            'custom_hours' => ['required_if:duration,custom', 'nullable', 'integer', 'min:1', 'max:720'],
            'reason' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($userId);

        $hours = match ($data['duration']) {
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            'custom' => $data['custom_hours'],
        };

        $bannedUntil = now()->addHours($hours);
        $old = ['status' => $user->status, 'banned_until' => $user->banned_until];

        $user->update([
            'status' => 'banned',
            'banned_until' => $bannedUntil,
        ]);

        AdminLog::log('user.tempban', $user, $old, [
            'status' => 'banned',
            'banned_until' => $bannedUntil->toIso8601String(),
            'duration' => $data['duration'],
            'reason' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'user' => $user,
            'message' => "Usuario baneado hasta {$bannedUntil->diffForHumans()}.",
        ]);
    }

    public function restorePost($postId): JsonResponse
    {
        $post = \App\Models\Post::withTrashed()->findOrFail($postId);
        $post->restore();

        AdminLog::log('post.restore', $post, ['deleted_at' => null], $post->toArray());

        return response()->json([
            'post' => $post,
            'message' => 'Publicación restaurada.',
        ]);
    }
}
