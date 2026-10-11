<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModerationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    // ─── Bloqueos: efecto real ─────────────────────────────────────────────

    public function test_blocked_user_posts_disappear_from_feed_in_both_directions(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Post::create(['user_id' => $alice->id, 'content' => 'Post de Alice']);
        Post::create(['user_id' => $bob->id, 'content' => 'Post de Bob']);

        UserBlock::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);

        // Alice (blocker) no ve a Bob
        Sanctum::actingAs($alice);
        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonMissingPath('data.1')
            ->assertJsonPath('data.0.id', Post::where('user_id', $alice->id)->first()->id);

        // Bob (blocked) no ve a Alice
        Sanctum::actingAs($bob);
        $response = $this->getJson('/api/posts')->assertOk();
        $contents = collect($response->json('data'))->pluck('content');
        $this->assertContains('Post de Bob', $contents);
        $this->assertNotContains('Post de Alice', $contents);
    }

    public function test_blocked_user_comments_disappear(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $post = Post::create(['user_id' => $alice->id, 'content' => 'Post']);
        Comment::create(['user_id' => $bob->id, 'post_id' => $post->id, 'content' => 'Comentario oculto']);

        UserBlock::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);

        Sanctum::actingAs($alice);
        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonMissingPath('data.0');
    }

    public function test_blocked_user_disappears_from_search(): void
    {
        $alice = User::factory()->create(['name' => 'Alice Searchable', 'username' => 'alice_s']);
        $bob = User::factory()->create(['name' => 'Bob Searchable', 'username' => 'bob_s']);

        UserBlock::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);

        Sanctum::actingAs($alice);
        $response = $this->getJson('/api/users/search?q=Searchable')->assertOk();
        $usernames = collect($response->json('data'))->pluck('username');
        $this->assertContains('alice_s', $usernames);
        $this->assertNotContains('bob_s', $usernames);
    }

    public function test_blocked_user_profile_returns_404(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create(['username' => 'bob_profile']);

        UserBlock::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);

        Sanctum::actingAs($alice);
        $this->getJson('/api/users/bob_profile')->assertNotFound();

        // Y en la dirección inversa: Bob no ve el perfil de Alice
        $aliceUsername = $alice->username;
        UserBlock::create(['blocker_id' => $bob->id, 'blocked_id' => $alice->id]);
        Sanctum::actingAs($bob);
        $this->getJson("/api/users/{$aliceUsername}")->assertNotFound();
    }

    public function test_cannot_create_dm_with_blocked_user_either_direction(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        // Alice bloquea a Bob → Alice no puede iniciar chat
        UserBlock::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);
        Sanctum::actingAs($alice);
        $this->postJson('/api/conversations', ['user_id' => $bob->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        // Bob (bloqueado) tampoco puede iniciar chat con Alice
        Sanctum::actingAs($bob);
        $this->postJson('/api/conversations', ['user_id' => $alice->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    public function test_cannot_send_message_in_existing_dm_after_block(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        $conversation->users()->attach([$alice->id, $bob->id]);

        UserBlock::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);

        Sanctum::actingAs($bob);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Hola'])
            ->assertForbidden();

        Sanctum::actingAs($alice);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Hola'])
            ->assertForbidden();
    }

    public function test_user_can_still_see_and_unblock_their_block_list(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create(['username' => 'bob_blocklist']);

        UserBlock::create(['blocker_id' => $alice->id, 'blocked_id' => $bob->id]);

        Sanctum::actingAs($alice);

        // La lista de bloqueados sigue viendo al usuario bloqueado
        $this->getJson('/api/users/blocked')
            ->assertOk()
            ->assertJsonPath('data.0.username', 'bob_blocklist');

        // Y se puede desbloquear (sin 404 por el scope)
        $this->postJson("/api/users/{$bob->id}/block")
            ->assertOk()
            ->assertJsonPath('blocked', false);

        $this->assertDatabaseMissing('user_blocks', [
            'blocker_id' => $alice->id,
            'blocked_id' => $bob->id,
        ]);
    }

    // ─── Shadowban: contenido oculto solo para el autor ────────────────────

    public function test_shadowbanned_posts_visible_only_to_author(): void
    {
        $shadow = User::factory()->create(['is_shadowbanned' => true]);
        $viewer = User::factory()->create();
        $post = Post::create(['user_id' => $shadow->id, 'content' => 'Post shadowbanned']);

        // El autor lo ve en su feed (no se entera del shadowban)
        Sanctum::actingAs($shadow);
        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.id', $post->id);

        // Otro usuario no lo ve (sin error, simplemente ausente)
        Sanctum::actingAs($viewer);
        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonMissingPath('data.0');
    }

    public function test_shadowbanned_comments_visible_only_to_author(): void
    {
        $shadow = User::factory()->create(['is_shadowbanned' => true]);
        $viewer = User::factory()->create();
        $post = Post::create(['user_id' => $viewer->id, 'content' => 'Post abierto']);
        Comment::create(['user_id' => $shadow->id, 'post_id' => $post->id, 'content' => 'Comentario shadow']);

        Sanctum::actingAs($shadow);
        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Comentario shadow');

        Sanctum::actingAs($viewer);
        $this->getJson("/api/posts/{$post->id}/comments")
            ->assertOk()
            ->assertJsonMissingPath('data.0');
    }

    public function test_shadowbanned_post_returns_404_for_others_on_direct_endpoints(): void
    {
        $shadow = User::factory()->create(['is_shadowbanned' => true]);
        $viewer = User::factory()->create();
        $post = Post::create(['user_id' => $shadow->id, 'content' => 'Oculto']);

        Sanctum::actingAs($viewer);
        $this->postJson("/api/posts/{$post->id}/like")->assertNotFound();
        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'x'])->assertNotFound();
    }

    public function test_admin_sees_shadowbanned_content(): void
    {
        $shadow = User::factory()->create(['is_shadowbanned' => true]);
        $admin = User::factory()->create(['is_global_admin' => true]);
        Post::create(['user_id' => $shadow->id, 'content' => 'Visible para admin']);

        Sanctum::actingAs($admin);
        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.0.content', 'Visible para admin');
    }

    public function test_shadowbanned_user_can_still_comment_without_error(): void
    {
        $shadow = User::factory()->create(['is_shadowbanned' => true]);
        $author = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'content' => 'Post']);

        Sanctum::actingAs($shadow);
        // No recibe error: el comentario se crea, simplemente otros no lo ven
        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Mi comentario'])
            ->assertCreated();

        $this->assertDatabaseHas('comments', [
            'user_id' => $shadow->id,
            'post_id' => $post->id,
            'content' => 'Mi comentario',
        ]);
    }
}
