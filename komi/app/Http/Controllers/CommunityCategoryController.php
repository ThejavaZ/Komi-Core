<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommunityCategoryResource;
use App\Models\Community;
use App\Models\CommunityCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommunityCategoryController extends Controller
{
    /**
     * Categorias (flairs) de una comunidad. Lectura publica.
     */
    public function index(Community $community): AnonymousResourceCollection
    {
        $categories = $community->categories()
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return CommunityCategoryResource::collection($categories);
    }

    /**
     * Crear categoria: solo owner/admin de la comunidad.
     */
    public function store(Request $request, Community $community): JsonResponse
    {
        $this->authorizeStaff($request, $community);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:30', 'min:2'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if ($community->categories()->where('name', $data['name'])->exists()) {
            return response()->json(['message' => 'Ya existe una categoría con ese nombre.'], 422);
        }

        if ($community->categories()->count() >= 15) {
            return response()->json(['message' => 'Máximo 15 categorías por comunidad.'], 422);
        }

        $category = $community->categories()->create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría creada.',
            'category' => new CommunityCategoryResource($category),
        ], 201);
    }

    public function update(Request $request, Community $community, CommunityCategory $category): JsonResponse
    {
        $this->authorizeStaff($request, $community);
        abort_unless($category->community_id === $community->id, 404, 'Categoría no encontrada.');

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:30', 'min:2'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        if (isset($data['name']) && $community->categories()
            ->where('name', $data['name'])
            ->where('id', '!=', $category->id)
            ->exists()) {
            return response()->json(['message' => 'Ya existe una categoría con ese nombre.'], 422);
        }

        $category->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría actualizada.',
            'category' => new CommunityCategoryResource($category->fresh()),
        ]);
    }

    public function destroy(Request $request, Community $community, CommunityCategory $category): JsonResponse
    {
        $this->authorizeStaff($request, $community);
        abort_unless($category->community_id === $community->id, 404, 'Categoría no encontrada.');

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría eliminada.',
        ]);
    }

    private function authorizeStaff(Request $request, Community $community): void
    {
        $member = $community->members()->where('users.id', $request->user()->id)->first();

        if (!$member || !in_array($member->pivot->role, ['owner', 'admin'])) {
            abort(403, 'Solo el staff de la comunidad puede gestionar categorías.');
        }
    }
}
