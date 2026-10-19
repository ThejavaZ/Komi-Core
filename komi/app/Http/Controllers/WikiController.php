<?php

namespace App\Http\Controllers;

use App\Models\CommunityUser;
use App\Models\Wiki;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WikiController extends Controller
{
    /**
     * Detalle de un wiki: contenido actual + metadatos.
     * Aplica visibilidad del post via ContentVisibilityScope.
     */
    public function show(Request $request, Wiki $wiki): JsonResponse
    {
        $post = $wiki->post;
        abort_unless($post, 404, 'Wiki no encontrado.');

        $latest = $wiki->versions()->with('editor')->orderByDesc('version')->first();

        return response()->json([
            'status' => 'success',
            'data' => $this->payload($wiki, $post, $latest),
        ]);
    }

    /**
     * Actualizar el contenido de un wiki: crea una version en el historial.
     */
    public function update(Request $request, Wiki $wiki): JsonResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
            'edit_summary' => ['nullable', 'string', 'max:500'],
        ]);

        $post = $wiki->post;
        abort_unless($post, 404, 'Wiki no encontrado.');

        $this->authorizeEdit($request, $wiki, $post);

        DB::transaction(function () use ($wiki, $post, $data, $request) {
            $wiki->increment('version');
            $wiki->update(['last_editor_id' => $request->user()->id]);

            $wiki->versions()->create([
                'editor_id' => $request->user()->id,
                'version' => $wiki->version,
                'content' => $data['content'],
                'edit_summary' => $data['edit_summary'] ?? null,
            ]);

            $post->content = $data['content'];
            $post->edited_at = now();
            $post->save();
        });

        $wiki->refresh();
        $post->refresh();
        $latest = $wiki->versions()->with('editor')->orderByDesc('version')->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Wiki actualizado.',
            'data' => $this->payload($wiki, $post, $latest),
        ]);
    }

    /**
     * Historial de versiones (sin contenido, para la lista).
     */
    public function versions(Request $request, Wiki $wiki): JsonResponse
    {
        $post = $wiki->post;
        abort_unless($post, 404, 'Wiki no encontrado.');

        $versions = $wiki->versions()
            ->with('editor')
            ->orderByDesc('version')
            ->get()
            ->map(fn ($v) => [
                'version' => (int) $v->version,
                'edit_summary' => $v->edit_summary,
                'created_at' => $v->created_at?->toISOString(),
                'editor' => $v->editor ? [
                    'id' => $v->editor->id,
                    'name' => $v->editor->name,
                    'username' => $v->editor->username,
                ] : null,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $versions,
        ]);
    }

    /**
     * Contenido puntual de una version (para ver / diff).
     */
    public function versionDetail(Request $request, Wiki $wiki, int $version): JsonResponse
    {
        $post = $wiki->post;
        abort_unless($post, 404, 'Wiki no encontrado.');

        $found = $wiki->versions()->with('editor')->where('version', $version)->first();
        abort_unless($found, 404, 'La versión no existe.');

        return response()->json([
            'status' => 'success',
            'data' => [
                'version' => (int) $found->version,
                'content' => $found->content,
                'edit_summary' => $found->edit_summary,
                'created_at' => $found->created_at?->toISOString(),
                'editor' => $found->editor ? [
                    'id' => $found->editor->id,
                    'name' => $found->editor->name,
                    'username' => $found->editor->username,
                ] : null,
            ],
        ]);
    }

    /**
     * Revertir a una version anterior: la restaura como version NUEVA.
     */
    public function rollback(Request $request, Wiki $wiki): JsonResponse
    {
        $data = $request->validate([
            'version' => ['required', 'integer'],
        ]);

        $post = $wiki->post;
        abort_unless($post, 404, 'Wiki no encontrado.');

        $this->authorizeEdit($request, $wiki, $post);

        $source = $wiki->versions()->where('version', $data['version'])->first();
        abort_unless($source, 404, 'La versión no existe.');

        if ($source->version === $wiki->version) {
            abort(422, 'Esa versión ya es la actual.');
        }

        DB::transaction(function () use ($wiki, $post, $source, $request) {
            $wiki->increment('version');
            $wiki->update(['last_editor_id' => $request->user()->id]);

            $wiki->versions()->create([
                'editor_id' => $request->user()->id,
                'version' => $wiki->version,
                'content' => $source->content,
                'edit_summary' => "Revertido a la versión {$source->version}",
            ]);

            $post->content = $source->content;
            $post->edited_at = now();
            $post->save();
        });

        $wiki->refresh();
        $post->refresh();

        return response()->json([
            'status' => 'success',
            'message' => "Revertido a la versión {$source->version}.",
            'data' => [
                'version' => (int) $wiki->version,
                'content' => $post->content,
            ],
        ]);
    }

    /**
     * Autorizacion de edicion: autor del post, owner/admin de la
     * comunidad del wiki o admin global.
     */
    private function authorizeEdit(Request $request, Wiki $wiki, \App\Models\Post $post): void
    {
        $user = $request->user();

        if ($user->is_global_admin || $post->user_id === $user->id) {
            return;
        }

        if ($wiki->community_id && CommunityUser::query()
            ->where('community_id', $wiki->community_id)
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists()) {
            return;
        }

        abort(403, 'No tienes permiso para editar este wiki.');
    }

    private function payload(Wiki $wiki, \App\Models\Post $post, $latest): array
    {
        return [
            'id' => $wiki->id,
            'post_id' => $wiki->post_id,
            'title' => $wiki->title,
            'slug' => $wiki->slug,
            'cover_image' => $wiki->cover_image,
            'version' => (int) $wiki->version,
            'community_id' => $wiki->community_id,
            'content' => $latest?->content ?? $post->content,
            'created_at' => $wiki->created_at?->toISOString(),
            'updated_at' => $wiki->updated_at?->toISOString(),
            'author' => $post->user ? [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'username' => $post->user->username,
                'avatar_url' => $post->user->avatar,
            ] : null,
        ];
    }
}
