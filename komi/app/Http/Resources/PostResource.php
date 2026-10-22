<?php

namespace App\Http\Resources;

use App\Models\Bookmark;
use App\Models\CommunityUser;
use App\Models\PollVote;
use App\Models\Post;
use App\Models\Vote;
use App\Support\Xp;
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
                : Bookmark::where('user_id', $userId)->where('post_id', $this->id)->exists();
        }

        // Repost data
        $isReposted = false;
        if ($userId && ! $deleted) {
            $isReposted = Post::where('user_id', $userId)
                ->where('original_post_id', $this->id)
                ->where('type', 'repost')
                ->exists();
        }

        return [
            'id' => $this->id,
            'content' => $deleted ? 'Publicación no disponible' : $this->content,
            'type' => $deleted ? 'text' : ($this->type ?? 'text'),
            'ama_status' => $deleted ? null : $this->ama_status,
            'media_url' => $deleted ? null : $this->image_url,
            'image_url' => $deleted ? null : $this->image_url,
            'audio_url' => $deleted ? null : $this->audio_url,
            'images' => $deleted ? [] : $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'position' => (int) $img->position,
            ])->values()),
            'link_url' => $deleted ? null : $this->link_url,
            'video_provider' => $deleted ? null : $this->video_provider,
            'video_id' => $deleted ? null : $this->video_id,
            'list_items' => $this->whenLoaded('listItems', fn () => $this->listItems->map(fn ($item) => [
                'id' => $item->id,
                'position' => (int) $item->position,
                'text' => $item->text,
            ])->values()),
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
                // originalPost puede ser null si su autor esta bloqueado o
                // shadowbanned para el viewer (ContentVisibilityScope).
                if ($this->originalPost === null || $this->originalPost->trashed()) {
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
                    'user' => $this->originalPost->user ? [
                        'id' => $this->originalPost->user->id,
                        'name' => $this->originalPost->user->name,
                        'username' => $this->originalPost->user->username,
                        'avatar_url' => $this->originalPost->user->avatar,
                    ] : null,
                ];
            }),
            'quiz' => $this->whenLoaded('quiz', function () use ($deleted, $userId) {
                if ($deleted) {
                    return null;
                }

                $myAttempt = $userId
                    ? $this->quiz->attempts()->where('user_id', $userId)->first()
                    : null;

                return [
                    'id' => $this->quiz->id,
                    'title' => $this->quiz->title,
                    'description' => $this->quiz->description,
                    'show_results_immediately' => $this->quiz->show_results_immediately,
                    'allow_retake' => $this->quiz->allow_retake,
                    'time_limit_seconds' => $this->quiz->time_limit_seconds,
                    'my_attempt' => $myAttempt ? [
                        'score' => (int) $myAttempt->score,
                        'total_questions' => (int) $myAttempt->total_questions,
                        'created_at' => $myAttempt->created_at?->toISOString(),
                    ] : null,
                    'questions' => $this->quiz->questions->map(fn ($q) => [
                        'id' => $q->id,
                        'question' => $q->question,
                        'explanation' => $q->explanation,
                        'order' => $q->order,
                        'answers' => $q->answers->map(fn ($a) => [
                            'id' => $a->id,
                            'answer_text' => $a->answer_text,
                        ]),
                    ]),
                ];
            }),
            'wiki' => $this->whenLoaded('wiki', function () use ($deleted) {
                if ($deleted) {
                    return null;
                }

                return [
                    'id' => $this->wiki->id,
                    'title' => $this->wiki->title,
                    'slug' => $this->wiki->slug,
                    'cover_image' => $this->wiki->cover_image,
                    'version' => $this->wiki->version,
                    'content' => $this->content,
                    'post_id' => $this->wiki->post_id,
                    'community_id' => $this->wiki->community_id,
                ];
            }),
            'question' => $this->whenLoaded('question', function () use ($deleted) {
                if ($deleted) {
                    return null;
                }

                return [
                    'id' => $this->question->id,
                    'is_solved' => $this->question->is_solved,
                    'accepted_answer_id' => $this->question->accepted_answer_id,
                ];
            }),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'is_pinned' => $deleted ? false : (bool) $this->is_pinned,
            'is_featured' => $deleted ? false : (bool) $this->is_featured,
            'can_feature' => $deleted ? false : $this->canFeatureFor($request, $userId),
            'is_edited' => ! $deleted && ! is_null($this->edited_at),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'community' => $this->whenLoaded('community', fn () => $this->community ? [
                'id' => $this->community->id,
                'name' => $this->community->name,
                'slug' => $this->community->slug,
            ] : null),
            'category' => $this->whenLoaded('communityCategory', fn () => $this->communityCategory ? [
                'id' => $this->communityCategory->id,
                'name' => $this->communityCategory->name,
                'color' => $this->communityCategory->color,
            ] : null),
            'deleted' => $deleted,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $deleted ? 'Usuario eliminado' : $this->user->name,
                'username' => $deleted ? 'usuario_eliminado' : $this->user->username,
                'avatar_url' => $deleted ? null : $this->user->avatar,
                'is_verified' => $deleted ? false : (bool) $this->user->is_verified,
                'level' => $deleted ? null : Xp::levelFor((int) $this->user->xp),
            ] : null),
            'author_id' => $deleted ? null : $this->user_id,
            'poll' => $pollData,
        ];
    }

    /**
     * El viewer puede destacar este post si es admin global o
     * owner/admin de su comunidad (usa viewerMembership eager-load).
     */
    private function canFeatureFor(Request $request, ?int $userId): bool
    {
        if (! $userId || ! $this->community_id) {
            return false;
        }

        if ($request->user()->is_global_admin) {
            return true;
        }

        if ($this->relationLoaded('viewerMembership')) {
            return in_array($this->viewerMembership?->role, ['owner', 'admin'], true);
        }

        return CommunityUser::query()
            ->where('community_id', $this->community_id)
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }
}
