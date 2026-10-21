<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommunityEventResource;
use App\Models\Community;
use App\Models\CommunityEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommunityEventController extends Controller
{
    /**
     * Eventos de la comunidad ordenados por fecha de inicio.
     */
    public function index(Request $request, Community $community): AnonymousResourceCollection
    {
        $query = $community->events()->with('user:id,name,username')->orderBy('starts_at');

        if ($request->boolean('upcoming')) {
            $query->where(function ($q) {
                $q->where('ends_at', '>=', now())
                    ->orWhere('starts_at', '>=', now());
            });
        }

        return CommunityEventResource::collection($query->get());
    }

    /**
     * Crear evento: solo owner/admin.
     */
    public function store(Request $request, Community $community): JsonResponse
    {
        $this->authorizeStaff($request, $community);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $data['user_id'] = $request->user()->id;
        $event = $community->events()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Evento creado.',
            'event' => new CommunityEventResource($event->load('user:id,name,username')),
        ], 201);
    }

    public function update(Request $request, Community $community, CommunityEvent $event): JsonResponse
    {
        $this->authorizeStaff($request, $community);
        abort_unless($event->community_id === $community->id, 404, 'Evento no encontrado.');

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $event->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Evento actualizado.',
            'event' => new CommunityEventResource($event->fresh()->load('user:id,name,username')),
        ]);
    }

    public function destroy(Request $request, Community $community, CommunityEvent $event): JsonResponse
    {
        $this->authorizeStaff($request, $community);
        abort_unless($event->community_id === $community->id, 404, 'Evento no encontrado.');

        $event->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Evento eliminado.',
        ]);
    }

    private function authorizeStaff(Request $request, Community $community): void
    {
        $member = $community->members()->where('users.id', $request->user()->id)->first();

        if (!$member || !in_array($member->pivot->role, ['owner', 'admin'])) {
            abort(403, 'Solo el staff de la comunidad puede gestionar eventos.');
        }
    }
}
