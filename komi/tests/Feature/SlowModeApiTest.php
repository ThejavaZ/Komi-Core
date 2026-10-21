<?php

namespace Tests\Feature;

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SlowModeApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_user_can_post_within_hour_limit(): void
    {
        $this->makeUser();

        $this->postJson('/api/posts', ['content' => 'Hola', 'type' => 'text'])
            ->assertCreated();
    }

    public function test_post_is_rate_limited_after_hourly_cap(): void
    {
        $user = $this->makeUser();

        Post::factory()->count(PostController::MAX_POSTS_PER_HOUR)->create([
            'user_id' => $user->id,
            'type' => 'text',
            'created_at' => now(),
        ]);

        $this->postJson('/api/posts', ['content' => 'Spam', 'type' => 'text'])
            ->assertStatus(429);
    }

    public function test_post_limit_resets_after_an_hour(): void
    {
        $user = $this->makeUser();

        Post::factory()->count(PostController::MAX_POSTS_PER_HOUR)->create([
            'user_id' => $user->id,
            'type' => 'text',
            'created_at' => now()->subHours(2),
        ]);

        $this->postJson('/api/posts', ['content' => 'De nuevo', 'type' => 'text'])
            ->assertCreated();
    }

    public function test_comment_is_rate_limited_after_hourly_cap(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create(['user_id' => $user->id, 'type' => 'text']);

        \App\Models\Comment::factory()->count(CommentController::MAX_COMMENTS_PER_HOUR)->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now(),
        ]);

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Spam comment'])
            ->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }
}
