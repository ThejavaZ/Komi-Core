<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    /**
     * Register or update a user's vote on a post.
     *
     * POST /api/posts/{post}/vote
     * Body: { "type": "upvote" | "downvote" | "none" }
     */
    public function vote(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:upvote,downvote,none'],
        ]);

        $userId = $request->user()->id;
        $type = $request->input('type');

        DB::transaction(function () use ($post, $userId, $type) {
            $existingVote = Vote::query()
                ->where('user_id', $userId)
                ->where('post_id', $post->id)
                ->first();

            if ($type === 'none') {
                if ($existingVote) {
                    // Remove vote
                    $delta = $existingVote->type === 'upvote' ? -1 : 1;
                    $post->increment('vote_score', $delta);
                    $existingVote->delete();
                }
            } else {
                if ($existingVote) {
                    if ($existingVote->type === $type) {
                        // Same vote, no change
                        return;
                    }
                    // Switch vote direction
                    $delta = $type === 'upvote' ? 2 : -2;
                    $existingVote->update(['type' => $type]);
                    $post->increment('vote_score', $delta);
                } else {
                    // New vote
                    Vote::create([
                        'user_id' => $userId,
                        'post_id' => $post->id,
                        'type' => $type,
                    ]);
                    $delta = $type === 'upvote' ? 1 : -1;
                    $post->increment('vote_score', $delta);
                }
            }
        });

        $post->refresh();

        $userVote = Vote::query()
            ->where('user_id', $userId)
            ->where('post_id', $post->id)
            ->value('type');

        return response()->json([
            'status' => 'success',
            'vote_score' => (int) $post->vote_score,
            'user_vote' => $userVote,
        ]);
    }
}
