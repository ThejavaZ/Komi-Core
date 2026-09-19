<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunityRequest;
use App\Http\Requests\UpdateCommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Models\Community;
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

        return new CommunityResource($community);
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
            ->withPivot('role')
            ->orderByPivot('role')
            ->orderBy('name')
            ->get();

        return \App\Http\Resources\FollowerResource::collection($members);
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
}
