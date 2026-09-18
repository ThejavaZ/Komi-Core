<?php

namespace App\Http\Resources;

use App\Models\PollVote;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deleted = $this->trashed();
        $isLiked = ! $deleted && (bool) ($this->is_liked_by_me ?? false);

        $userId = $request->user()?->id;

        // Poll data
        $pollData = null;
        if (! $deleted && $this->type === 'poll' && $this->relationLoaded('pollOptions')) {
            $totalVotes = $this->pollOptions->sum('votes_count');
            $userVotedOptionId = null;

            if ($userId) {
                $userVotedOptionId = PollVote::query()
                    ->where('user_id', $userId)
                    ->whereHas('pollOption', fn ($q) => $q->where('post_id', $this->id))
                    ->value('poll_option_id');
            }

            $pollData = [
                'options' => $this->pollOptions->map(fn ($opt) => [
                    'id' => $opt->id,
                    'option_text' => $opt->option_text,
                    'votes_count' => (int) $opt->votes_count,
                ]),
                'total_votes' => $totalVotes,
                'user_voted_option_id' => $userVotedOptionId,
            ];
        }

        // Vote data
        $userVote = null;
        if ($userId && ! $deleted) {
            $userVote = $this->relationLoaded('votes')
                ? ($this->votes->where('user_id', $userId)->first()?->type)
                : Vote::where('user_id', $userId)->where('post_id', $this->id)->value('type');
        }

        // Bookmark data
        $isBookmarked = false;
        if ($userId && ! $deleted) {
            $isBookmarked = $this->relationLoaded('bookmarks')
                ? $this->bookmarks->contains('user_id', $userId)
                : \App\Models\Bookmark::where('user_id', $userId)->where('post_id', $this->id)->exists();
        }

        // Repost data
        $isReposted = false;
        if ($userId && ! $deleted) {
            $isReposted = \App\Models\Post::where('user_id', $userId)
                ->where('original_post_id', $this->id)
                ->where('type', 'repost')
                ->exists();
        }

        return [
            'id' => $this->id,
            'content' => $deleted ? 'Publicación no disponible' : $this->content,
            'type' => $deleted ? 'text' : ($this->type ?? 'text'),
            'media_url' => $deleted ? null : $this->image_url,
            'image_url' => $deleted ? null : $this->image_url,
            'likes_count' => $deleted ? 0 : (int) $this->likes_count,
            'comments_count' => $deleted ? 0 : (int) ($this->comments_count ?? 0),
            'vote_score' => $deleted ? 0 : (int) ($this->vote_score ?? 0),
            'reposts_count' => $deleted ? 0 : (int) ($this->reposts_count ?? 0),
            'is_liked' => $isLiked,
            'is_liked_by_me' => $isLiked,
            'user_vote' => $deleted ? null : $userVote,
            'is_bookmarked' => $deleted ? false : $isBookmarked,
            'is_reposted' => $deleted ? false : $isReposted,
            'original_post_id' => $deleted ? null : $this->original_post_id,
            'original_post' => $this->whenLoaded('originalPost', function () {
                if ($this->originalPost->trashed()) {
                    return null;
                }
                return [
                    'id' => $this->originalPost->id,
                    'content' => $this->originalPost->content,
                    'type' => $this->originalPost->type,
                    'image_url' => $this->originalPost->image_url,
                    'vote_score' => (int) $this->originalPost->vote_score,
                    'likes_count' => (int) $this->originalPost->likes_count,
                    'comments_count' => (int) $this->originalPost->comments_count,
                    'reposts_count' => (int) $this->originalPost->reposts_count,
                    'created_at' => $this->originalPost->created_at?->toISOString(),
                    'user' => [
                        'id' => $this->originalPost->user->id,
                        'name' => $this->originalPost->user->name,
                        'username' => $this->originalPost->user->username,
                        'avatar_url' => $this->originalPost->user->avatar,
                    ],
                ];
            }),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'community' => $this->whenLoaded('community', fn () => $this->community ? [
                'id' => $this->community->id,
                'name' => $this->community->name,
                'slug' => $this->community->slug,
            ] : null),
            'deleted' => $deleted,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $deleted ? 'Usuario eliminado' : $this->user->name,
                'username' => $deleted ? 'usuario_eliminado' : $this->user->username,
                'avatar_url' => $deleted ? null : $this->user->avatar,
                'is_verified' => $deleted ? false : (bool) $this->user->is_verified,
            ]),
            'author_id' => $deleted ? null : $this->user_id,
            'poll' => $pollData,
        ];
    }
}
