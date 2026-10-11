<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
    }

    public function test_like_creates_notification_for_post_owner(): void
    {
        $owner = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'content' => 'Hola']);

        Sanctum::actingAs($liker);
        $this->postJson("/api/posts/{$post->id}/like")->assertOk();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'from_user_id' => $liker->id,
            'type' => 'like',
            'post_id' => $post->id,
        ]);
    }

    public function test_like_on_own_post_does_not_notify(): void
    {
        $owner = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'content' => 'Solo']);

        Sanctum::actingAs($owner);
        $this->postJson("/api/posts/{$post->id}/like")->assertOk();

        $this->assertSame(0, AppNotification::count());
    }

    public function test_comment_creates_notification(): void
    {
        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'content' => 'Post']);

        Sanctum::actingAs($commenter);
        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Buen post'])
            ->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'from_user_id' => $commenter->id,
            'type' => 'comment',
        ]);
    }

    public function test_follow_creates_notification(): void
    {
        $target = User::factory()->create();
        $follower = User::factory()->create();

        Sanctum::actingAs($follower);
        $this->postJson("/api/users/{$target->id}/follow")->assertOk();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $target->id,
            'from_user_id' => $follower->id,
            'type' => 'follow',
        ]);
    }

    public function test_user_can_list_notifications(): void
    {
        $me = User::factory()->create();
        $actor = User::factory()->create();
        $post = Post::create(['user_id' => $me->id, 'content' => 'P']);
        AppNotification::notify($me, $actor, AppNotification::TYPE_COMMENT, $post->id);

        Sanctum::actingAs($me);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'comment')
            ->assertJsonPath('data.0.from_user.id', $actor->id)
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_unread_count_and_mark_read(): void
    {
        $me = User::factory()->create();
        $actor = User::factory()->create();

        AppNotification::notify($me, $actor, AppNotification::TYPE_FOLLOW);
        AppNotification::notify($me, $actor, AppNotification::TYPE_FOLLOW);

        Sanctum::actingAs($me);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 2);

        $notification = AppNotification::first();

        $this->postJson("/api/notifications/{$notification->id}/read")->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);

        $this->postJson('/api/notifications/read-all')->assertOk();
        $this->assertSame(0, AppNotification::whereNull('read_at')->count());
    }

    public function test_user_cannot_mark_others_notification(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $actor = User::factory()->create();
        $notification = AppNotification::notify($other, $actor, AppNotification::TYPE_FOLLOW);

        Sanctum::actingAs($me);

        $this->postJson("/api/notifications/{$notification->id}/read")->assertForbidden();
    }
}
