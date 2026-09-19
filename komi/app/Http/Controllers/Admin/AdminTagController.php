<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTagController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tags,name'],
        ]);

        $tag = Tag::create($data);
        AdminLog::log('tag.create', $tag, null, $data);

        return response()->json(['tag' => $tag], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $old = $tag->toArray();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:tags,name,' . $id],
        ]);

        $tag->update($data);
        AdminLog::log('tag.update', $tag, $old, $data);

        return response()->json(['tag' => $tag]);
    }

    public function destroy($id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        AdminLog::log('tag.delete', $tag, $tag->toArray());
        $tag->delete();

        return response()->json(['message' => 'Tag eliminado.']);
    }
}
