<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostImages;
use App\Models\Reaction;
use App\Models\Tag;
use App\Support\BadgeAwarder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /** Límite "modo lento" anti-spam: máximo de posts por hora por usuario. */
    public const MAX_POSTS_PER_HOUR = 15;

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = Post::query()
            ->with(['user', 'community', 'communityCategory', 'pollOptions', 'tags', 'originalPost.user', 'quiz.questions.answers', 'wiki', 'question', 'listItems', 'viewerMembership', 'images'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)]);

        // Modo galería: solo posts con imagen (estilo Instagram/Pinterest).
        if ($request->input('mode') === 'gallery') {
            $query->where(function ($q) {
                $q->where('type', 'image')
                    ->orWhereNotNull('image_url')
                    ->orWhereHas('images');
            });
        }

        $posts = $query
            ->orderByDesc('is_featured')
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate($perPage);

        return PostResource::collection($posts);
    }

    /**
     * Feed "Siguiendo": posts unicamente de los usuarios que sigue el viewer.
     */
    public function following(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $posts = Post::query()
            ->whereIn('user_id', function ($query) use ($request) {
                $query->select('following_id')
                    ->from('followers')
                    ->where('follower_id', $request->user()->id);
            })
            ->with(['user', 'community', 'communityCategory', 'pollOptions', 'tags', 'originalPost.user', 'quiz.questions.answers', 'wiki', 'question', 'listItems', 'viewerMembership'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate($perPage);

        return PostResource::collection($posts);
    }

    /**
     * Post individual (para deep-linking desde notificaciones / busqueda).
     * El binding aplica ContentVisibilityScope: ocultos devuelven 404.
     */
    public function show(Request $request, Post $post): PostResource
    {
        $post->load(['user', 'community', 'communityCategory', 'pollOptions', 'tags', 'originalPost.user', 'quiz.questions.answers', 'wiki', 'question', 'listItems', 'viewerMembership'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)]);

        return new PostResource($post);
    }

    public function store(StorePostRequest $request, \App\Services\AutoModService $autoMod): PostResource
    {
        $data = $request->validated();

        // Modo lento anti-spam: máximo de posts por hora.
        $recentPosts = Post::where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recentPosts >= self::MAX_POSTS_PER_HOUR) {
            abort(429, 'Has alcanzado el límite de ' . self::MAX_POSTS_PER_HOUR . ' publicaciones por hora. Intenta de nuevo más tarde.');
        }

        // Automod: block/delete rechazan, flag crea reporte tras guardar.
        $automodHit = $autoMod->check($data['content'] ?? '');
        if ($automodHit && in_array($automodHit['action'], ['block', 'delete'], true)) {
            abort(422, 'Tu publicación contiene contenido no permitido (automod).');
        }

        // Validar membresia si postea en una comunidad
        if (!empty($data['community_id'])) {
            $community = \App\Models\Community::find($data['community_id']);
            if (!$community || !$community->members()->where('users.id', $request->user()->id)->exists()) {
                abort(403, 'Debes ser miembro de la comunidad para publicar en ella.');
            }
        }

        // La categoria debe pertenecer a la misma comunidad del post
        if (!empty($data['community_category_id'])) {
            $category = \App\Models\CommunityCategory::find($data['community_category_id']);
            abort_unless(
                $category && (int) $category->community_id === (int) ($data['community_id'] ?? 0),
                422,
                'La categoría no pertenece a esa comunidad.'
            );
        }

        $imageUrl = $data['image_url'] ?? null;
        $type = $data['type'] ?? 'text';

        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('posts', 'public');
        }

        // Audio de fondo del post (pistas cortas: mp3/aac/m4a/ogg/wav ≤ 5MB).
        $audioUrl = null;
        if ($request->hasFile('audio')) {
            $audioUrl = $request->file('audio')->store('posts-audio', 'public');
        }

        $galleryPaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $galleryPaths[] = $file->store('posts', 'public');
            }

            if ($imageUrl === null) {
                $imageUrl = $galleryPaths[0];
            }
        }

        if (!empty($data['poll_options']) && is_array($data['poll_options'])) {
            $type = 'poll';
        } elseif (!empty($data['quiz_questions'])) {
            $type = 'quiz';
        } elseif (!empty($data['wiki_title'])) {
            $type = 'wiki';
        } elseif ($type === 'video') {
            if (empty($data['link_url'])) {
                abort(422, 'Un post de video necesita una URL.');
            }

            $parsed = $this->parseVideoUrl($data['link_url']);
            if ($parsed === null) {
                abort(422, 'URL de video no soportada. Usa un enlace de YouTube o Vimeo.');
            }
        } elseif (!empty($data['list_items'])) {
            $type = 'list';
        } elseif (!empty($data['link_url'])) {
            $type = 'link';
        }

        $video = $type === 'video' ? $this->parseVideoUrl($data['link_url']) : null;

        $post = $request->user()->posts()->create([
            'community_id' => $data['community_id'] ?? null,
            'community_category_id' => $data['community_category_id'] ?? null,
            'content' => $data['content'],
            'type' => $type,
            'ama_status' => $type === 'ama' ? 'open' : null,
            'image_url' => $imageUrl,
            'audio_url' => $audioUrl,
            'link_url' => $data['link_url'] ?? null,
            'video_provider' => $video[0] ?? null,
            'video_id' => $video[1] ?? null,
        ]);

        BadgeAwarder::award($request->user(), 'first_post');

        if ($automodHit && $automodHit['action'] === 'flag') {
            $autoMod->flag('App\\Models\\Post', $post->id, $automodHit['keyword'], $request->user()->id);
        }

        foreach ($galleryPaths as $index => $path) {
            $post->images()->create([
                'url' => $path,
                'position' => $index,
            ]);
        }

        // ── Structured content creation ──────────────────────────────

        if ($type === 'poll' && !empty($data['poll_options'])) {
            foreach ($data['poll_options'] as $optionText) {
                $post->pollOptions()->create([
                    'option_text' => $optionText,
                ]);
            }
        }

        if ($type === 'quiz' && !empty($data['quiz_questions'])) {
            $quiz = $post->quiz()->create([
                'title' => $data['quiz_title'] ?? 'Quiz',
                'description' => $data['quiz_description'] ?? null,
            ]);

            foreach ($data['quiz_questions'] as $order => $qData) {
                $question = $quiz->questions()->create([
                    'question' => $qData['question'],
                    'explanation' => $qData['explanation'] ?? null,
                    'order' => $order,
                ]);

                foreach ($qData['answers'] as $aOrder => $aAnswer) {
                    $question->answers()->create([
                        'answer_text' => $aAnswer['text'],
                        'is_correct' => $aAnswer['is_correct'] ?? false,
                        'order' => $aOrder,
                    ]);
                }
            }
        }

        if ($type === 'wiki' && !empty($data['wiki_title'])) {
            $wiki = $post->wiki()->create([
                'title' => $data['wiki_title'],
                'community_id' => $data['community_id'] ?? null,
                'last_editor_id' => $request->user()->id,
            ]);

            $wiki->versions()->create([
                'editor_id' => $request->user()->id,
                'version' => 1,
                'content' => $data['content'],
            ]);
        }

        if ($type === 'question') {
            $post->question()->create([
                'is_solved' => $data['is_solved'] ?? false,
            ]);
        }

        if ($type === 'list' && !empty($data['list_items'])) {
            foreach ($data['list_items'] as $position => $text) {
                $post->listItems()->create([
                    'position' => $position,
                    'text' => $text,
                ]);
            }
        }

        if (!empty($data['tags']) && is_array($data['tags'])) {
            $tagIds = collect($data['tags'])
                ->map(function (string $tagName) {
                    $clean = strtolower(ltrim(trim($tagName), '#'));
                    $clean = preg_replace('/[^a-z0-9áéíóúñü_-]/u', '', $clean);
                    return $clean;
                })
                ->filter()
                ->unique()
                ->take(5)
                ->map(fn (string $slug) => Tag::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $slug]
                )->id)
                ->toArray();

            $post->tags()->sync($tagIds);
        }

        $post->load(['user', 'community', 'communityCategory', 'pollOptions', 'tags', 'images', 'quiz.questions.answers', 'wiki', 'question', 'listItems', 'viewerMembership'])->loadCount('comments');
        $post->is_liked_by_me = false;

        return (new PostResource($post))->additional([
            'status' => 'success',
            'message' => 'Publicación creada con éxito.',
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403, 'No tienes permiso para editar esta publicación.');
        }

        $data = $request->validated();

        if (isset($data['content'])) {
            $post->content = $data['content'];
        }

        if (isset($data['community_id'])) {
            $post->community_id = $data['community_id'];
        }

        if ($request->hasFile('image')) {
            $post->image_url = $request->file('image')->store('posts', 'public');
        }

        $post->edited_at = now();

        if ($request->has('is_pinned')) {
            $post->is_pinned = $request->boolean('is_pinned');
        }

        $post->save();

        // Editar el contenido de un wiki crea una nueva version en el historial
        if ($post->type === 'wiki' && $post->wasChanged('content')) {
            $wiki = $post->wiki;

            if ($wiki) {
                $wiki->update([
                    'version' => $wiki->version + 1,
                    'last_editor_id' => $request->user()->id,
                ]);

                $wiki->versions()->create([
                    'editor_id' => $request->user()->id,
                    'version' => $wiki->version,
                    'content' => $post->content,
                    'edit_summary' => $data['edit_summary'] ?? null,
                ]);
            }
        }

        $post->load(['user', 'community', 'communityCategory', 'tags', 'images', 'quiz.questions.answers', 'wiki', 'question', 'listItems', 'viewerMembership'])->loadCount('comments');

        return (new PostResource($post))->additional([
            'status' => 'success',
            'message' => 'Publicación actualizada.',
        ]);
    }

    public function addImages(Request $request, Post $post): PostResource
    {
        if ($post->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para editar esta publicación.');
        }

        $data = $request->validate([
            'images' => ['required', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $existing = $post->images()->count();

        if ($existing + count($data['images']) > 5) {
            abort(422, 'Máximo 5 imágenes por publicación.');
        }

        foreach ($data['images'] as $index => $file) {
            $post->images()->create([
                'url' => $file->store('posts', 'public'),
                'position' => $existing + $index,
            ]);
        }

        if ($post->image_url === null) {
            $post->image_url = $post->images()->orderBy('position')->first()?->url;
            $post->save();
        }

        $post->load(['user', 'community', 'communityCategory', 'tags', 'images', 'quiz.questions.answers', 'wiki', 'question', 'listItems', 'viewerMembership'])->loadCount('comments');

        return (new PostResource($post))->additional([
            'status' => 'success',
            'message' => 'Imágenes agregadas.',
        ]);
    }

    public function removeImage(Request $request, Post $post, PostImages $image): JsonResponse
    {
        if ($post->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para editar esta publicación.');
        }

        if ($image->post_id !== $post->id) {
            abort(404, 'La imagen no pertenece a esta publicación.');
        }

        Storage::disk('public')->delete($image->url);

        if ($post->image_url === $image->url) {
            $next = $post->images()
                ->whereKeyNot($image->id)
                ->orderBy('position')
                ->first();

            $post->image_url = $next?->url;
            $post->save();
        }

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Imagen eliminada.',
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403, 'No tienes permiso para eliminar esta publicación.');
        }

        $post->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Publicación eliminada.',
        ]);
    }

    public function toggleLike(Request $request, Post $post): JsonResponse
    {
        $userId = $request->user()->id;

        $liked = DB::transaction(function () use ($post, $userId): bool {
            $reaction = Reaction::query()
                ->where('user_id', $userId)
                ->where('post_id', $post->id)
                ->lockForUpdate()
                ->first();

            if ($reaction) {
                $reaction->delete();
                $post->decrement('likes_count');

                return false;
            }

            Reaction::create([
                'user_id' => $userId,
                'post_id' => $post->id,
            ]);
            $post->increment('likes_count');

            return true;
        });

        if ($liked) {
            $post->loadMissing('user');
            if ($post->user) {
                \App\Models\AppNotification::notify(
                    $post->user,
                    $request->user(),
                    \App\Models\AppNotification::TYPE_LIKE,
                    $post->id,
                );

                if ($post->likes_count >= 10) {
                    BadgeAwarder::award($post->user, 'popular_post');
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'liked' => $liked,
            'likes_count' => (int) $post->refresh()->likes_count,
        ]);
    }

    public function togglePin(Request $request, Post $post): JsonResponse
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403, 'No tienes permiso para fijar esta publicación.');
        }

        $post->is_pinned = !$post->is_pinned;
        $post->save();

        return response()->json([
            'status' => 'success',
            'is_pinned' => $post->is_pinned,
            'message' => $post->is_pinned ? 'Publicación fijada.' : 'Publicación despinada.',
        ]);
    }

    /**
     * AMA: abrir o cerrar preguntas. Solo el anfitrión del post.
     */
    public function setAmaStatus(Request $request, Post $post): JsonResponse
    {
        if ($post->type !== 'ama') {
            return response()->json(['message' => 'Esta publicación no es un AMA.'], 422);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el anfitrión puede cambiar el estado del AMA.'], 403);
        }

        $data = $request->validate([
            'ama_status' => ['required', 'string', \Illuminate\Validation\Rule::in(['open', 'closed'])],
        ]);

        $post->update(['ama_status' => $data['ama_status']]);

        return response()->json([
            'status' => 'success',
            'ama_status' => $post->ama_status,
        ]);
    }

    /**
     * Destacar / quitar destaque (solo moderadores de la comunidad o admin global).
     */
    public function toggleFeature(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $isModerator = $user->is_global_admin
            || ($post->community_id && \App\Models\CommunityUser::query()
                ->where('community_id', $post->community_id)
                ->where('user_id', $user->id)
                ->whereIn('role', ['owner', 'admin'])
                ->exists());

        if (!$isModerator) {
            abort(403, 'Solo los moderadores pueden destacar publicaciones.');
        }

        $post->is_featured = !$post->is_featured;
        $post->save();

        return response()->json([
            'status' => 'success',
            'is_featured' => $post->is_featured,
            'message' => $post->is_featured
                ? 'Publicación destacada.'
                : 'Publicación quitada de destacados.',
        ]);
    }

    /**
     * Extrae proveedor e id de URLs de video soportadas (YouTube / Vimeo).
     *
     * @return array{0: string, 1: string}|null
     */
    private function parseVideoUrl(string $url): ?array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            if (str_starts_with($path, '/watch') && !empty($query['v']) && preg_match('/^[\w-]{5,20}$/', $query['v'])) {
                return ['youtube', $query['v']];
            }
            if (preg_match('#^/shorts/([\w-]{5,20})#', $path, $m)) {
                return ['youtube', $m[1]];
            }
            if (preg_match('#^/embed/([\w-]{5,20})#', $path, $m)) {
                return ['youtube', $m[1]];
            }

            return null;
        }

        if ($host === 'youtu.be') {
            $id = ltrim($path, '/');

            return preg_match('/^[\w-]{5,20}$/', $id) ? ['youtube', $id] : null;
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)
            && preg_match('#^/(?:video/)?(\d{6,15})(?:/|$)#', $path, $m)) {
            return ['vimeo', $m[1]];
        }

        return null;
    }
}
