<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChallengeResource;
use App\Models\Challenge;
use App\Models\Community;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommunityChallengeController extends Controller
{
    /**
     * Retos de la comunidad ordenados por deadline.
     */
    public function index(Request $request, Community $community): AnonymousResourceCollection
    {
        $challenges = $community->challenges()
            ->withCount('participants')
            ->withExists([
                'participants as joined_by_me' => fn ($q) => $q->where('users.id', $request->user()->id),
            ])
            ->orderBy('deadline')
            ->get();

        return ChallengeResource::collection($challenges);
    }

    /**
     * Crear reto: solo staff, con deadline obligatorio.
     */
    public function store(Request $request, Community $community): JsonResponse
    {
        $this->authorizeStaff($request, $community);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'deadline' => ['required', 'date', 'after:now'],
        ]);
        $data['user_id'] = $request->user()->id;

        $challenge = $community->challenges()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Reto creado.',
            'challenge' => new ChallengeResource($challenge),
        ], 201);
    }

    /**
     * Editar reto o marcarlo como completado (staff).
     */
    public function update(Request $request, Community $community, Challenge $challenge): JsonResponse
    {
        $this->authorizeStaff($request, $community);
        abort_unless($challenge->community_id === $community->id, 404, 'Reto no encontrado.');

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'deadline' => ['sometimes', 'required', 'date'],
            'is_completed' => ['sometimes', 'boolean'],
        ]);

        $challenge->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Reto actualizado.',
            'challenge' => new ChallengeResource($challenge->fresh()),
        ]);
    }

    public function destroy(Request $request, Community $community, Challenge $challenge): JsonResponse
    {
        $this->authorizeStaff($request, $community);
        abort_unless($challenge->community_id === $community->id, 404, 'Reto no encontrado.');

        $challenge->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reto eliminado.',
        ]);
    }

    /**
     * Unirse a un reto abierto (antes del deadline y sin completar).
     */
    public function join(Request $request, Community $community, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->community_id === $community->id, 404, 'Reto no encontrado.');

        if ($challenge->is_completed) {
            return response()->json(['message' => 'El reto ya está completado.'], 422);
        }

        if ($challenge->deadline->isPast()) {
            return response()->json(['message' => 'El deadline de este reto ya pasó.'], 422);
        }

        $userId = $request->user()->id;
        if ($challenge->participants()->where('users.id', $userId)->exists()) {
            return response()->json(['message' => 'Ya participas en este reto.'], 422);
        }

        $challenge->participants()->attach($userId);

        return response()->json([
            'status' => 'success',
            'message' => '¡Te uniste al reto!',
            'participants_count' => $challenge->participants()->count(),
        ]);
    }

    /**
     * Salir de un reto.
     */
    public function leave(Request $request, Community $community, Challenge $challenge): JsonResponse
    {
        abort_unless($challenge->community_id === $community->id, 404, 'Reto no encontrado.');

        $userId = $request->user()->id;
        if (!$challenge->participants()->where('users.id', $userId)->exists()) {
            return response()->json(['message' => 'No participas en este reto.'], 422);
        }

        $challenge->participants()->detach($userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Saliste del reto.',
            'participants_count' => $challenge->participants()->count(),
        ]);
    }

    private function authorizeStaff(Request $request, Community $community): void
    {
        $member = $community->members()->where('users.id', $request->user()->id)->first();

        if (!$member || !in_array($member->pivot->role, ['owner', 'admin'])) {
            abort(403, 'Solo el staff de la comunidad puede gestionar retos.');
        }
    }
}
