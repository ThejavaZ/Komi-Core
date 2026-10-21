<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunityRequest;
use App\Http\Requests\UpdateCommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Models\Community;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 15), 50);

        $query = Community::withCount(['posts', 'members'])
            ->with('owner:id,name,username,avatar');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $communities = $query->latest()->paginate($perPage);

        return CommunityResource::collection($communities);
    }

    public function store(StoreCommunityRequest $request): CommunityResource
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['owner_id'] = $request->user()->id;

        $community = Community::create($data);

        // Owner se une como miembro con rol owner
        $community->members()->attach($request->user()->id, ['role' => 'owner']);

        \App\Support\BadgeAwarder::award($request->user(), 'community_founder');

        $community->loadCount(['posts', 'members']);
        $community->load('owner:id,name,username,avatar');

        return (new CommunityResource($community))->additional([
            'status' => 'success',
            'message' => 'Comunidad creada con éxito.',
        ]);
    }

    public function show(Community $community): CommunityResource
    {
        $community->loadCount(['posts', 'members']);
        $community->load('owner:id,name,username,avatar');
        $community->load('members:id,name,username,avatar');
        $community->load('categories');

        return new CommunityResource($community);
    }

    /**
     * Feed de posts de una comunidad (paginado, mas recientes primero).
     */
    public function posts(Request $request, Community $community): AnonymousResourceCollection
    {
        $posts = $community->posts()
            ->with(['user', 'community', 'communityCategory', 'tags', 'pollOptions', 'originalPost.user', 'quiz.questions.answers', 'wiki', 'question'])
            ->withCount('comments')
            ->withExists(['reactions as is_liked_by_me' => fn ($q) => $q->where('user_id', $request->user()->id)])
            ->when($request->filled('category_id'), fn ($q) => $q->where('community_category_id', $request->integer('category_id')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 50));

        return \App\Http\Resources\PostResource::collection($posts);
    }

    public function update(UpdateCommunityRequest $request, Community $community): JsonResponse
    {
        $userId = $request->user()->id;

        // Solo owner o admin pueden editar
        $member = $community->members()->where('users.id', $userId)->first();
        if (!$member || !in_array($member->pivot->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'No tienes permiso para editar esta comunidad.'], 403);
        }

        $community->update($request->validated());

        $community->loadCount(['posts', 'members']);
        $community->load('owner:id,name,username,avatar');

        return response()->json([
            'status' => 'success',
            'message' => 'Comunidad actualizada.',
            'community' => new CommunityResource($community),
        ]);
    }

    public function destroy(Request $request, Community $community): JsonResponse
    {
        if ($community->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el owner puede eliminar la comunidad.'], 403);
        }

        $community->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Comunidad eliminada.',
        ]);
    }

    // ─── Membership ──────────────────────────────────────────

    public function join(Request $request, Community $community): JsonResponse
    {
        if ($community->members()->where('users.id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Ya eres miembro de esta comunidad.'], 400);
        }

        $community->members()->attach($request->user()->id, ['role' => 'member']);

        return response()->json([
            'status' => 'success',
            'message' => 'Te uniste a la comunidad.',
            'members_count' => $community->fresh()->members()->count(),
        ]);
    }

    public function leave(Request $request, Community $community): JsonResponse
    {
        $userId = $request->user()->id;

        if (!$community->members()->where('users.id', $userId)->exists()) {
            return response()->json(['message' => 'No eres miembro de esta comunidad.'], 400);
        }

        if ($community->owner_id === $userId) {
            return response()->json(['message' => 'El owner no puede salir de su propia comunidad.'], 400);
        }

        $community->members()->detach($userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Saliste de la comunidad.',
            'members_count' => $community->fresh()->members()->count(),
        ]);
    }

    public function members(Community $community): AnonymousResourceCollection
    {
        $members = $community->members()
            ->withPivot(['role', 'custom_title'])
            ->orderByPivot('role')
            ->orderBy('name')
            ->get();

        return \App\Http\Resources\CommunityMemberResource::collection($members);
    }

    public function removeMember(Request $request, Community $community, int $userId): JsonResponse
    {
        $requesterId = $request->user()->id;
        $requester = $community->members()->where('users.id', $requesterId)->first();

        if (!$requester || !in_array($requester->pivot->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'No tienes permiso para expulsar miembros.'], 403);
        }

        if ($userId === $community->owner_id) {
            return response()->json(['message' => 'No puedes expulsar al owner.'], 403);
        }

        $community->members()->detach($userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Miembro expulsado.',
            'members_count' => $community->fresh()->members()->count(),
        ]);
    }

    /**
     * Cambia el rol de un miembro. Solo staff; jerarquía:
     * - El rol del owner nunca cambia.
     * - Un admin no puede modificar a otros admins (solo el owner).
     */
    public function updateRole(Request $request, Community $community, User $user): JsonResponse
    {
        $requester = $community->members()
            ->where('users.id', $request->user()->id)
            ->first();

        if (!$requester || !in_array($requester->pivot->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'No tienes permiso para cambiar roles.'], 403);
        }

        $target = $community->members()->where('users.id', $user->id)->first();
        if (!$target) {
            return response()->json(['message' => 'El usuario no es miembro de esta comunidad.'], 404);
        }

        $data = $request->validate([
            'role' => ['required', 'string', \Illuminate\Validation\Rule::in(['admin', 'member'])],
        ]);

        if ($target->pivot->role === 'owner') {
            return response()->json(['message' => 'El rol del owner no se puede cambiar.'], 422);
        }

        if ($requester->pivot->role === 'admin' && $target->pivot->role === 'admin') {
            return response()->json(['message' => 'Solo el owner puede modificar a otros admins.'], 403);
        }

        $community->members()->updateExistingPivot($user->id, ['role' => $data['role']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rol actualizado.',
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $data['role'],
            ],
        ]);
    }

    /**
     * Otorga o retira un "placa"/título personal en la comunidad.
     * Solo staff; no se puede titular al owner.
     */
    public function updateTitle(Request $request, Community $community, User $user): JsonResponse
    {
        $requester = $community->members()
            ->where('users.id', $request->user()->id)
            ->first();

        if (!$requester || !in_array($requester->pivot->role, ['owner', 'admin'])) {
            return response()->json(['message' => 'No tienes permiso para otorgar títulos.'], 403);
        }

        $target = $community->members()->where('users.id', $user->id)->first();
        if (!$target) {
            return response()->json(['message' => 'El usuario no es miembro de esta comunidad.'], 404);
        }

        if ($target->pivot->role === 'owner') {
            return response()->json(['message' => 'No se puede poner un título al owner.'], 422);
        }

        $data = $request->validate([
            'custom_title' => ['present', 'nullable', 'string', 'max:30'],
        ]);

        $community->members()->updateExistingPivot($user->id, [
            'custom_title' => $data['custom_title'] ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $data['custom_title'] ? 'Título otorgado.' : 'Título retirado.',
            'member' => [
                'id' => $user->id,
                'custom_title' => $data['custom_title'] ?: null,
            ],
        ]);
    }
}
