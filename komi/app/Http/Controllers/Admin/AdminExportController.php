<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AdminExportController extends Controller
{
    public function users(): Response
    {
        $users = User::select('id', 'name', 'username', 'email', 'status', 'is_verified', 'is_global_admin', 'warnings_count', 'created_at')
            ->withCount('posts')
            ->latest()
            ->get();

        return $this->buildCsvResponse($users, 'usuarios', [
            'ID', 'Nombre', 'Username', 'Email', 'Estado', 'Verificado', 'Admin', 'Warnings', 'Posts', 'Registro',
        ], function ($user) {
            return [
                $user->id,
                $user->name,
                $user->username,
                $user->email,
                $user->status,
                $user->is_verified ? 'Sí' : 'No',
                $user->is_global_admin ? 'Sí' : 'No',
                $user->warnings_count,
                $user->posts_count,
                $user->created_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function posts(): Response
    {
        $posts = Post::with('user:id,name,username')
            ->select('id', 'user_id', 'content', 'type', 'likes_count', 'comments_count', 'created_at')
            ->latest()
            ->get();

        return $this->buildCsvResponse($posts, 'publicaciones', [
            'ID', 'Autor', 'Username', 'Contenido', 'Tipo', 'Likes', 'Comentarios', 'Fecha',
        ], function ($post) {
            return [
                $post->id,
                $post->user?->name ?? 'N/A',
                $post->user?->username ?? 'N/A',
                $post->content,
                $post->type,
                $post->likes_count,
                $post->comments_count,
                $post->created_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function reports(): Response
    {
        $reports = Report::with(['reporter:id,name,username'])
            ->latest()
            ->get();

        return $this->buildCsvResponse($reports, 'reportes', [
            'ID', 'Reportado por', 'Username', 'Tipo', 'Razón', 'Descripción', 'Estado', 'Fecha',
        ], function ($report) {
            return [
                $report->id,
                $report->reporter?->name ?? 'N/A',
                $report->reporter?->username ?? 'N/A',
                class_basename($report->reportable_type),
                $report->reason,
                $report->description ?? '',
                $report->status,
                $report->created_at?->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function singleUser($id): Response
    {
        $user = User::withCount(['posts', 'comments', 'reactions'])
            ->findOrFail($id);

        $posts = $user->posts()->select('id', 'content', 'type', 'likes_count', 'created_at')->get();

        $rows = [];
        $rows[] = ['=== DATOS DEL USUARIO ==='];
        $rows[] = ['ID', $user->id];
        $rows[] = ['Nombre', $user->name];
        $rows[] = ['Username', $user->username];
        $rows[] = ['Email', $user->email];
        $rows[] = ['Estado', $user->status];
        $rows[] = ['Verificado', $user->is_verified ? 'Sí' : 'No'];
        $rows[] = ['Admin', $user->is_global_admin ? 'Sí' : 'No'];
        $rows[] = ['Warnings', $user->warnings_count];
        $rows[] = ['Shadowbanned', $user->is_shadowbanned ? 'Sí' : 'No'];
        $rows[] = ['Registro', $user->created_at?->format('Y-m-d H:i:s')];
        $rows[] = [];
        $rows[] = ['=== ESTADÍSTICAS ==='];
        $rows[] = ['Posts', $user->posts_count];
        $rows[] = ['Comentarios', $user->comments_count];
        $rows[] = ['Reacciones', $user->reactions_count];
        $rows[] = [];
        $rows[] = ['=== PUBLICACIONES ==='];
        $rows[] = ['ID', 'Contenido', 'Tipo', 'Likes', 'Fecha'];
        foreach ($posts as $post) {
            $rows[] = [$post->id, $post->content, $post->type, $post->likes_count, $post->created_at?->format('Y-m-d H:i:s')];
        }

        $filename = "usuario_{$id}_" . now()->format('Y-m-d') . '.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildCsvResponse($data, $name, array $headers, callable $rowMapper): Response
    {
        $filename = "{$name}_" . now()->format('Y-m-d_H-i') . '.csv';
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headers);
        foreach ($data as $item) {
            fputcsv($handle, $rowMapper($item));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
