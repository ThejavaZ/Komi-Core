<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostImages;
use App\Models\Reaction;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $posts = Post::query()
            ->with(['user', 'community', 'pollOptions', 'tags', 'originalPost.user', 'quiz.questions.answers', 'wiki', 'question'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)])
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
            ->with(['user', 'community', 'pollOptions', 'tags', 'originalPost.user', 'quiz.questions.answers', 'wiki', 'question'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->orderByDesc('is_pinned')
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
        $post->load(['user', 'community', 'pollOptions', 'tags', 'originalPost.user', 'quiz.questions.answers', 'wiki', 'question'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)]);

        return new PostResource($post);
    }

    public function store(StorePostRequest $request): PostResource
    {
        $data = $request->validated();

        // Validar membresia si postea en una comunidad
        if (!empty($data['community_id'])) {
            $community = \App\Models\Community::find($data['community_id']);
            if (!$community || !$community->members()->where('users.id', $request->user()->id)->exists()) {
                abort(403, 'Debes ser miembro de la comunidad para publicar en ella.');
            }
        }

        $imageUrl = $data['image_url'] ?? null;
        $type = $data['type'] ?? 'text';

        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('posts', 'public');
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
        } elseif (!empty($data['link_url'])) {
            $type = 'link';
        }

        $post = $request->user()->posts()->create([
            'community_id' => $data['community_id'] ?? null,
            'content' => $data['content'],
            'type' => $type,
            'image_url' => $imageUrl,
            'link_url' => $data['link_url'] ?? null,
        ]);

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

        $post->load(['user', 'community', 'pollOptions', 'tags', 'images', 'quiz.questions.answers', 'wiki', 'question'])->loadCount('comments');
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

        $post->load(['user', 'community', 'tags', 'images', 'quiz.questions.answers', 'wiki', 'question'])->loadCount('comments');

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

        $post->load(['user', 'community', 'tags', 'images', 'quiz.questions.answers', 'wiki', 'question'])->loadCount('comments');

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
}
