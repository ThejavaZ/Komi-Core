<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint3DmNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createDirectConversation(User $a, User $b): Conversation
    {
        $conversation = Conversation::create(['type' => Conversation::TYPE_DIRECT]);
        $conversation->users()->attach([$a->id, $b->id]);

        return $conversation;
    }

    private function createGroupConversation(User $owner, array $members): Conversation
    {
        $conversation = Conversation::create(['type' => Conversation::TYPE_GROUP]);
        $conversation->users()->attach(
            array_merge([$owner->id], array_map(fn ($m) => $m->id, $members))
        );

        return $conversation;
    }

    public function test_sending_message_notifies_recipient(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Hola!'])
            ->assertCreated();

        $notification = AppNotification::where('user_id', $other->id)->first();

        $this->assertNotNull($notification);
        $this->assertSame(AppNotification::TYPE_MESSAGE, $notification->type);
        $this->assertSame($me->id, $notification->from_user_id);
        $this->assertSame($conversation->id, $notification->conversation_id);
        $this->assertNotNull($notification->message_id);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $other->id,
            'type' => AppNotification::TYPE_MESSAGE,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function test_sender_does_not_get_notification_for_own_message(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Hola!'])
            ->assertCreated();

        $this->assertSame(0, AppNotification::where('user_id', $me->id)->count());
        $this->assertSame(1, AppNotification::where('user_id', $other->id)->count());
    }

    public function test_group_message_notifies_all_other_participants(): void
    {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        $conversation = $this->createGroupConversation($owner, [$memberA, $memberB]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Hola grupo'])
            ->assertCreated();

        $this->assertSame(0, AppNotification::where('user_id', $owner->id)->count());
        $this->assertSame(1, AppNotification::where('user_id', $memberA->id)->count());
        $this->assertSame(1, AppNotification::where('user_id', $memberB->id)->count());
    }

    public function test_notification_api_exposes_conversation_and_message_ids(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Hola!']);

        Sanctum::actingAs($other);

        $response = $this->getJson('/api/notifications')->assertOk();

        $message = collect($response->json('data'))
            ->firstWhere('type', AppNotification::TYPE_MESSAGE);

        $this->assertNotNull($message);
        $this->assertSame($conversation->id, $message['conversation_id']);
        $this->assertNotNull($message['message_id']);
        $this->assertSame($me->id, $message['from_user']['id']);
    }

    public function test_notify_message_skips_when_recipient_is_actor(): void
    {
        $user = User::factory()->create();
        $conversation = $this->createDirectConversation($user, User::factory()->create());
        $message = $conversation->messages()->create(['user_id' => $user->id, 'content' => 'x']);

        $result = AppNotification::notifyMessage($user, $user, $conversation, $message);

        $this->assertNull($result);
    }
}
