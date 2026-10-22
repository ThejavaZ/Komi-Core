<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Xp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class XpLevelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_post_grants_xp(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', ['content' => 'Post con XP'])
            ->assertCreated();

        $this->assertSame(10, $user->fresh()->xp);
    }

    public function test_creating_comment_grants_xp(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Post ajeno']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Comentario con XP',
        ])->assertCreated();

        $this->assertSame(5, $user->fresh()->xp);
    }

    public function test_creating_community_grants_xp(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/communities', ['name' => 'Comunidad XP'])
            ->assertCreated();

        $this->assertSame(20, $user->fresh()->xp);
    }

    public function test_daily_checkin_grants_xp(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/checkin')->assertCreated();

        $this->assertSame(15, $user->fresh()->xp);
    }

    public function test_like_grants_author_xp_once_per_like(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Post que gusta']);

        Sanctum::actingAs($liker);

        $this->postJson("/api/posts/{$post->id}/like")->assertOk();
        $this->assertSame(2, $author->fresh()->xp);
        $this->assertSame(0, $liker->fresh()->xp);

        // Unlike no resta XP (el toggle simplemente quita el like).
        $this->postJson("/api/posts/{$post->id}/like")->assertOk();
        $this->assertSame(2, $author->fresh()->xp);
    }

    public function test_self_like_does_not_grant_xp(): void
    {
        $author = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Mi propio post']);

        Sanctum::actingAs($author);

        $this->postJson("/api/posts/{$post->id}/like")->assertOk();

        $this->assertSame(0, $author->fresh()->xp);
    }

    public function test_upvote_grants_author_xp_but_downvote_does_not(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Post a votar']);

        Sanctum::actingAs($voter);

        $this->postJson("/api/posts/{$post->id}/vote", ['type' => 'downvote'])
            ->assertOk();
        $this->assertSame(0, $author->fresh()->xp);

        // Cambiar downvote → upvote otorga XP de "like recibido".
        $this->postJson("/api/posts/{$post->id}/vote", ['type' => 'upvote'])
            ->assertOk();
        $this->assertSame(2, $author->fresh()->xp);
    }

    public function test_comment_like_grants_comment_author_xp(): void
    {
        $commenter = User::factory()->create();
        $author = User::factory()->create();
        $post = $author->posts()->create(['content' => 'Post con comentarios']);
        $comment = $commenter->comments()->create([
            'post_id' => $post->id,
            'content' => 'Comentario que gusta',
        ]);

        Sanctum::actingAs($author);

        $this->postJson("/api/comments/{$comment->id}/like")->assertOk();

        $this->assertSame(2, $commenter->fresh()->xp);
    }

    public function test_level_formula_boundaries(): void
    {
        $this->assertSame(1, Xp::levelFor(0));
        $this->assertSame(1, Xp::levelFor(49));
        $this->assertSame(2, Xp::levelFor(50));
        $this->assertSame(2, Xp::levelFor(199));
        $this->assertSame(3, Xp::levelFor(200));
        $this->assertSame(Xp::MAX_LEVEL, Xp::levelFor(1_000_000));
    }

    public function test_xp_summary_endpoint_returns_progress(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['xp' => 50])->save();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/xp/summary')
            ->assertOk()
            ->assertJsonPath('data.level', 2)
            ->assertJsonPath('data.level_name', 'Aprendiz')
            ->assertJsonPath('data.xp_total', 50)
            ->assertJsonPath('data.xp_into_level', 0)
            ->assertJsonPath('data.xp_for_next', 150)
            ->assertJsonPath('data.percent', 0)
            ->assertJsonPath('data.next_level', 3);

        $this->assertSame(10, $response->json('data.actions')['post.created']);
    }

    public function test_xp_summary_requires_auth(): void
    {
        $this->getJson('/api/xp/summary')->assertUnauthorized();
    }

    public function test_public_profile_exposes_level_fields(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['xp' => 200])->save();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/users/{$user->username}")
            ->assertOk()
            ->assertJsonPath('data.xp', 200)
            ->assertJsonPath('data.level', 3)
            ->assertJsonPath('data.level_name', 'Explorador');
    }
}
