<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function trending(Request $request): AnonymousResourceCollection
    {
        $limit = min((int) $request->input('limit', 10), 50);

        $tags = Tag::withCount('posts')
            ->orderByDesc('posts_count')
            ->limit($limit)
            ->get();

        return TagResource::collection($tags);
    }

    public function index(): AnonymousResourceCollection
    {
        return TagResource::collection(Tag::orderBy('name')->get());
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }
}
