<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\AutoModKeyword;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAutoModController extends Controller
{
    public function index(): JsonResponse
    {
        $keywords = AutoModKeyword::latest()->paginate(20);

        return response()->json($keywords);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'action' => ['required', 'string', 'in:flag,block,delete'],
        ]);

        $keyword = AutoModKeyword::create($data);
        AdminLog::log('automod.create', $keyword, null, $data);

        return response()->json(['keyword' => $keyword], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $keyword = AutoModKeyword::findOrFail($id);
        $old = $keyword->toArray();

        $data = $request->validate([
            'keyword' => ['sometimes', 'string', 'max:255'],
            'action' => ['sometimes', 'string', 'in:flag,block,delete'],
        ]);

        $keyword->update($data);
        AdminLog::log('automod.update', $keyword, $old, $data);

        return response()->json(['keyword' => $keyword]);
    }

    public function destroy($id): JsonResponse
    {
        $keyword = AutoModKeyword::findOrFail($id);
        AdminLog::log('automod.delete', $keyword, $keyword->toArray());
        $keyword->delete();

        return response()->json(['message' => 'Keyword eliminada.']);
    }
}
