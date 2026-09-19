<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'username', 'email', 'status'])
            ->map(fn ($u) => ['type' => 'user', 'id' => $u->id, 'title' => $u->name, 'subtitle' => "@{$u->username}", 'status' => $u->status]);
        $results = $results->merge($users);

        $posts = Post::where('content', 'like', "%{$query}%")
            ->with('user:id,name,username')
            ->limit(5)
            ->get()
            ->map(fn ($p) => ['type' => 'post', 'id' => $p->id, 'title' => \Illuminate\Support\Str::limit($p->content, 60), 'subtitle' => "por @{$p->user?->username}"]);
        $results = $results->merge($posts);

        $communities = Community::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => ['type' => 'community', 'id' => $c->id, 'title' => $c->name, 'subtitle' => $c->slug]);
        $results = $results->merge($communities);

        $tags = Tag::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn ($t) => ['type' => 'tag', 'id' => $t->id, 'title' => "#{$t->name}", 'subtitle' => 'tag']);
        $results = $results->merge($tags);

        return response()->json(['results' => $results->values()]);
    }
}
