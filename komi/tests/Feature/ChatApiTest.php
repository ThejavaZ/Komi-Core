<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    private function createDirectConversation(User $a, User $b): Conversation
    {
        $conversation = Conversation::create(['type' => 'direct']);
        $conversation->users()->attach([$a->id, $b->id]);

        return $conversation;
    }

    public function test_conversations_index_requires_authentication(): void
    {
        $this->getJson('/api/conversations')->assertUnauthorized();
    }

    public function test_user_can_create_direct_conversation(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($me);

        $this->postJson('/api/conversations', ['user_id' => $other->id])
            ->assertCreated()
            ->assertJsonPath('data.type', 'direct')
            ->assertJsonPath('data.other_user.id', $other->id);

        $this->assertDatabaseHas('conversation_users', ['user_id' => $me->id]);
        $this->assertDatabaseHas('conversation_users', ['user_id' => $other->id]);
    }

    public function test_creating_direct_conversation_reuses_existing(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $existing = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $this->postJson('/api/conversations', ['user_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id);

        $this->assertSame(1, Conversation::count());
    }

    public function test_user_cannot_create_conversation_with_self(): void
    {
        $me = User::factory()->create();

        Sanctum::actingAs($me);

        $this->postJson('/api/conversations', ['user_id' => $me->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    public function test_user_cannot_create_conversation_with_blocked_user(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $me->blockedUsers()->attach($other->id);

        Sanctum::actingAs($me);

        $this->postJson('/api/conversations', ['user_id' => $other->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');
    }

    public function test_user_can_list_conversations_with_unread_count(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        $conversation->messages()->create(['user_id' => $other->id, 'content' => 'Hola!']);
        $conversation->messages()->create(['user_id' => $other->id, 'content' => 'Aló?']);

        Sanctum::actingAs($me);

        $this->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonPath('data.0.id', $conversation->id)
            ->assertJsonPath('data.0.unread_count', 2)
            ->assertJsonPath('data.0.other_user.id', $other->id)
            ->assertJsonPath('data.0.last_message.content', 'Aló?');
    }

    public function test_non_participant_cannot_view_conversation(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $stranger = User::factory()->create();
        $conversation = $this->createDirectConversation($a, $b);

        Sanctum::actingAs($stranger);

        $this->getJson("/api/conversations/{$conversation->id}")->assertForbidden();
        $this->getJson("/api/conversations/{$conversation->id}/messages")->assertForbidden();
    }

    public function test_user_can_send_message_and_updates_last_message_at(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $this->postJson("/api/conversations/{$conversation->id}/messages", ['content' => 'Mensaje de prueba'])
            ->assertCreated()
            ->assertJsonPath('data.content', 'Mensaje de prueba')
            ->assertJsonPath('data.user.id', $me->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $me->id,
            'content' => 'Mensaje de prueba',
        ]);

        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_fetching_messages_marks_incoming_as_read(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);
        $conversation->messages()->create(['user_id' => $other->id, 'content' => 'No leido']);

        Sanctum::actingAs($me);

        $this->getJson("/api/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.content', 'No leido');

        $this->assertNotNull(Message::first()->read_at);
    }

    public function test_user_can_delete_own_message_only(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        $mine = $conversation->messages()->create(['user_id' => $me->id, 'content' => 'Mio']);
        $theirs = $conversation->messages()->create(['user_id' => $other->id, 'content' => 'Suyo']);

        Sanctum::actingAs($me);

        $this->deleteJson("/api/messages/{$theirs->id}")->assertForbidden();

        $this->deleteJson("/api/messages/{$mine->id}")->assertOk();
        $this->assertDatabaseMissing('messages', ['id' => $mine->id]);
    }

    public function test_user_can_create_group_conversation(): void
    {
        $me = User::factory()->create();
        $b = User::factory()->create();
        $c = User::factory()->create();

        Sanctum::actingAs($me);

        $this->postJson('/api/conversations/group', [
            'title' => 'Grupo de prueba',
            'user_ids' => [$b->id, $c->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'group')
            ->assertJsonPath('data.title', 'Grupo de prueba');

        $this->assertSame(3, Conversation::first()->users()->count());
    }

    public function test_user_can_leave_conversation(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);

        $this->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->assertDatabaseMissing('conversation_users', [
            'conversation_id' => $conversation->id,
            'user_id' => $me->id,
        ]);
        $this->assertNotNull(Conversation::find($conversation->id));
    }

    public function test_conversation_is_deleted_when_last_user_leaves(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $conversation = $this->createDirectConversation($me, $other);

        Sanctum::actingAs($me);
        $this->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        Sanctum::actingAs($other);
        $this->deleteJson("/api/conversations/{$conversation->id}")->assertOk();

        $this->assertNull(Conversation::find($conversation->id));
    }
}
