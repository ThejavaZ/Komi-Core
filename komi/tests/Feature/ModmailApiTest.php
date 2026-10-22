<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModmailApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Modmail',
            'slug' => 'comunidad-modmail-' . uniqid(),
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_member_opens_a_modmail_thread(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($member);

        $this->postJson("/api/communities/{$community->id}/modmail")
            ->assertCreated()
            ->assertJsonPath('data.type', 'modmail')
            ->assertJsonPath('data.community_id', $community->id);
    }

    public function test_open_reuses_existing_thread(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($member);

        $first = $this->postJson("/api/communities/{$community->id}/modmail");
        $first->assertCreated();

        $second = $this->postJson("/api/communities/{$community->id}/modmail");
        $second->assertOk();

        $this->assertSame(
            $first->json('data.id'),
            $second->json('data.id')
        );
    }

    public function test_non_member_cannot_open_modmail(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $outsider = User::factory()->create();
        Sanctum::actingAs($outsider);

        $this->postJson("/api/communities/{$community->id}/modmail")
            ->assertForbidden();
    }

    public function test_staff_lists_threads_and_can_reply(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($member);
        $thread = $this->postJson("/api/communities/{$community->id}/modmail")->assertCreated();
        $conversationId = $thread->json('id') ?? $thread->json('data.id');

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($owner);

        $this->getJson("/api/communities/{$community->id}/modmail")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // El owner puede escribir en el hilo aunque no sea participante.
        $this->postJson("/api/conversations/{$conversationId}/messages", [
            'content' => 'Hola, ¿en qué podemos ayudarte?',
        ])->assertCreated();

        // Y leer los mensajes.
        $this->getJson("/api/conversations/{$conversationId}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_regular_member_cannot_list_threads(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($member);

        $this->getJson("/api/communities/{$community->id}/modmail")
            ->assertForbidden();
    }

    public function test_unrelated_user_cannot_read_thread(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($member);
        $thread = $this->postJson("/api/communities/{$community->id}/modmail")->assertCreated();
        $conversationId = $thread->json('id') ?? $thread->json('data.id');

        $eavesdropper = User::factory()->create();
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($eavesdropper);

        $this->getJson("/api/conversations/{$conversationId}/messages")
            ->assertForbidden();
    }
}
