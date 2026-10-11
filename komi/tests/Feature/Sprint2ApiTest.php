<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint2ApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── 6: Post individual ────────────────────────────────────────────────

    public function test_user_can_fetch_single_post(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Post individual']);

        Sanctum::actingAs($viewer);
        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.content', 'Post individual')
            ->assertJsonPath('data.is_pinned', false);
    }

    public function test_single_post_of_blocked_user_returns_404(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Oculto']);

        \App\Models\UserBlock::create(['blocker_id' => $author->id, 'blocked_id' => $viewer->id]);

        Sanctum::actingAs($viewer);
        $this->getJson("/api/posts/{$post->id}")->assertNotFound();
    }

    public function test_single_post_includes_is_pinned(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'user_id' => $author->id,
            'content' => 'Fijado',
            'is_pinned' => true,
        ]);

        Sanctum::actingAs($author);
        $this->getJson("/api/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.is_pinned', true);
    }

    // ─── 8: Feed following ────────────────────────────────────────────────

    public function test_following_feed_only_returns_followed_users_posts(): void
    {
        $me = User::factory()->create();
        $followed = User::factory()->create();
        $stranger = User::factory()->create();

        Post::create(['user_id' => $followed->id, 'content' => 'Del seguido']);
        Post::create(['user_id' => $stranger->id, 'content' => 'Del desconocido']);

        $me->following()->attach($followed->id);

        Sanctum::actingAs($me);
        $response = $this->getJson('/api/posts/following')->assertOk();

        $contents = collect($response->json('data'))->pluck('content');
        $this->assertContains('Del seguido', $contents);
        $this->assertNotContains('Del desconocido', $contents);
    }

    public function test_following_feed_excludes_own_posts_unless_following_self(): void
    {
        $me = User::factory()->create();
        Post::create(['user_id' => $me->id, 'content' => 'Mi propio post']);

        Sanctum::actingAs($me);
        $this->getJson('/api/posts/following')
            ->assertOk()
            ->assertJsonMissingPath('data.0');
    }

    // ─── 7: Busqueda unificada ────────────────────────────────────────────

    public function test_search_returns_all_categories(): void
    {
        $me = User::factory()->create(['name' => 'Zeta Finder', 'username' => 'zeta_f']);
        Community::create(['name' => 'Zeta Club', 'slug' => 'zeta-club', 'owner_id' => $me->id]);
        Tag::create(['name' => 'zetatag', 'slug' => 'zetatag']);
        $author = User::factory()->create();
        Post::create(['user_id' => $author->id, 'content' => 'Contenido zeta']);

        Sanctum::actingAs($me);
        $response = $this->getJson('/api/search?q=zeta')->assertOk();

        $this->assertNotEmpty($response->json('users'));
        $this->assertNotEmpty($response->json('posts'));
        $this->assertNotEmpty($response->json('communities'));
        $this->assertNotEmpty($response->json('tags'));
    }

    public function test_search_with_empty_query_returns_empty_arrays(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/search?q=')
            ->assertOk()
            ->assertJson(['users' => [], 'posts' => [], 'communities' => [], 'tags' => []]);
    }

    // ─── Posts por hashtag ──────────────────────────────────────────────────

    public function test_user_can_fetch_posts_by_tag_slug(): void
    {
        $me = User::factory()->create();
        $tag = Tag::create(['name' => 'flutter', 'slug' => 'flutter']);
        $post = Post::create(['user_id' => $me->id, 'content' => 'Post con #flutter']);
        $post->tags()->attach($tag->id);

        Sanctum::actingAs($me);
        $this->getJson('/api/tags/flutter/posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $post->id);
    }

    public function test_tag_posts_with_unknown_slug_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/tags/noexiste/posts')->assertNotFound();
    }

    // ─── 11: Gestion de comentarios ───────────────────────────────────────

    public function test_user_can_edit_own_comment(): void
    {
        $author = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Post']);
        $comment = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Original',
        ]);

        Sanctum::actingAs($author);
        $this->putJson("/api/comments/{$comment->id}", ['content' => 'Editado'])
            ->assertOk()
            ->assertJsonPath('data.content', 'Editado')
            ->assertJsonPath('data.is_edited', true);
    }

    public function test_user_cannot_edit_others_comment(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'content' => 'Post']);
        $comment = Comment::create([
            'user_id' => $owner->id,
            'post_id' => $post->id,
            'content' => 'Del dueño',
        ]);

        Sanctum::actingAs($other);
        $this->putJson("/api/comments/{$comment->id}", ['content' => 'Hackeado'])
            ->assertForbidden();
    }

    public function test_user_can_delete_own_comment_and_counter_decrements(): void
    {
        $author = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Post', 'comments_count' => 1]);
        $comment = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Borrable',
        ]);

        Sanctum::actingAs($author);
        $this->deleteJson("/api/comments/{$comment->id}")
            ->assertOk();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertSame(0, (int) $post->refresh()->comments_count);
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'content' => 'Post']);
        $comment = Comment::create([
            'user_id' => $owner->id,
            'post_id' => $post->id,
            'content' => 'Del dueño',
        ]);

        Sanctum::actingAs($other);
        $this->deleteJson("/api/comments/{$comment->id}")->assertForbidden();
    }

    public function test_user_can_like_and_unlike_comment(): void
    {
        $owner = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'content' => 'Post']);
        $comment = Comment::create([
            'user_id' => $owner->id,
            'post_id' => $post->id,
            'content' => 'Comentable',
        ]);

        Sanctum::actingAs($liker);

        $this->postJson("/api/comments/{$comment->id}/like")
            ->assertOk()
            ->assertJsonPath('liked', true)
            ->assertJsonPath('likes_count', 1);

        $this->postJson("/api/comments/{$comment->id}/like")
            ->assertOk()
            ->assertJsonPath('liked', false)
            ->assertJsonPath('likes_count', 0);
    }

    public function test_comment_list_includes_like_state(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Post']);
        $comment = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Con likes',
        ]);
        $comment->likes_count = 3;
        $comment->save();

        Sanctum::actingAs($liker);
        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.likes_count', 3)
            ->assertJsonPath('data.0.is_liked_by_me', false);
    }
}
