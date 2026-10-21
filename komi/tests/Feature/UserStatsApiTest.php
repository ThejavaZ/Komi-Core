<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserStatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_totals_series_and_top_posts(): void
    {
        $user = User::factory()->create();

        $popular = $user->posts()->create([
            'content' => 'Post popular',
            'likes_count' => 42,
        ]);
        $user->posts()->create(['content' => 'Post normal', 'likes_count' => 3]);
        $user->comments()->create([
            'post_id' => $popular->id,
            'content' => 'Comentario',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}/stats")
            ->assertOk()
            ->assertJsonPath('data.totals.posts', 2)
            ->assertJsonPath('data.totals.comments', 1)
            ->assertJsonPath('data.totals.likes_received', 45);

        $series = $response->json('data.posts_last_30_days');
        $this->assertCount(30, $series);
        $this->assertEquals(2, array_sum(array_column($series, 'count')));

        $top = $response->json('data.top_posts');
        $this->assertEquals($popular->id, $top[0]['id']);
        $this->assertEquals(42, $top[0]['likes_count']);
    }

    public function test_stats_of_another_user_are_visible(): void
    {
        $owner = User::factory()->create();
        $owner->posts()->create(['content' => 'De otro', 'likes_count' => 1]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/users/{$owner->id}/stats")
            ->assertOk()
            ->assertJsonPath('data.totals.posts', 1);
    }
}
