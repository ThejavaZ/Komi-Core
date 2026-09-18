<?php

namespace App\Http\Controllers;

use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    /**
     * Register or update a user's vote on a poll atomically.
     *
     * POST /api/posts/{post}/poll/vote
     * Body: { "option_id": 3 }
     */
    public function vote(Request $request, Post $post): JsonResponse
    {
        if ($post->type !== 'poll') {
            return response()->json([
                'status' => 'error',
                'message' => 'Esta publicación no es una encuesta.',
            ], 422);
        }

        $request->validate([
            'option_id' => ['required', 'integer', 'exists:poll_options,id'],
        ]);

        $option = PollOption::findOrFail($request->input('option_id'));

        if ($option->post_id !== $post->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'La opción no pertenece a esta encuesta.',
            ], 422);
        }

        $userId = $request->user()->id;

        DB::transaction(function () use ($post, $option, $userId) {
            // Find existing vote on any option for this post
            $existingVote = PollVote::query()
                ->where('user_id', $userId)
                ->whereHas('pollOption', fn ($q) => $q->where('post_id', $post->id))
                ->first();

            if ($existingVote) {
                if ($existingVote->poll_option_id === $option->id) {
                    // Same option - no change
                    return;
                }

                // Move vote: decrement old, increment new
                PollOption::whereKey($existingVote->poll_option_id)->decrement('votes_count');
                $existingVote->update(['poll_option_id' => $option->id]);
                $option->increment('votes_count');
            } else {
                // New vote
                PollVote::create([
                    'user_id' => $userId,
                    'poll_option_id' => $option->id,
                ]);
                $option->increment('votes_count');
            }
        });

        // Return updated poll state
        $post->load('pollOptions');

        $totalVotes = $post->pollOptions->sum('votes_count');
        $userVote = PollVote::query()
            ->where('user_id', $userId)
            ->whereHas('pollOption', fn ($q) => $q->where('post_id', $post->id))
            ->value('poll_option_id');

        return response()->json([
            'status' => 'success',
            'poll' => [
                'options' => $post->pollOptions->map(fn (PollOption $opt) => [
                    'id' => $opt->id,
                    'option_text' => $opt->option_text,
                    'votes_count' => $opt->votes_count,
                ]),
                'total_votes' => $totalVotes,
                'user_voted_option_id' => $userVote,
            ],
        ]);
    }
}
