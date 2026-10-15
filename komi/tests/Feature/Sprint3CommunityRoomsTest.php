<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Sprint3CommunityRoomsTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunity(User $owner): Community
    {
        $community = Community::create([
            'name' => 'Komi Gaming',
            'slug' => 'komi-gaming',
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return $community;
    }

    public function test_non_member_cannot_list_rooms(): void
    {
        $owner = User::factory()->create();
        $community = $this->createCommunity($owner);
        $outsider = User::factory()->create();

        Sanctum::actingAs($outsider);

        $this->getJson("/api/communities/{$community->id}/rooms")->assertForbidden();
    }

    public function test_member_can_list_rooms(): void
    {
        $owner = User::factory()->create();
        $community = $this->createCommunity($owner);

        Sanctum::actingAs($owner);

        $this->getJson("/api/communities/{$community->id}/rooms")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_owner_can_create_room(): void
    {
        $owner = User::factory()->create();
        $community = $this->createCommunity($owner);

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/rooms", [
            'title' => 'Sala General',
        ])->assertCreated()
            ->assertJsonPath('data.type', 'room')
            ->assertJsonPath('data.community_id', $community->id)
            ->assertJsonPath('data.title', 'Sala General');

        $room = Conversation::where('community_id', $community->id)->first();
        $this->assertNotNull($room);
        $this->assertTrue($room->isParticipant($owner));
    }

    public function test_regular_member_cannot_create_room(): void
    {
        $owner = User::factory()->create();
        $community = $this->createCommunity($owner);
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($member);

        $this->postJson("/api/communities/{$community->id}/rooms", [
            'title' => 'Sala No Oficial',
        ])->assertForbidden();
    }

    public function test_community_member_can_join_room(): void
    {
        $owner = User::factory()->create();
        $community = $this->createCommunity($owner);
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        $room = Conversation::create([
            'type' => Conversation::TYPE_ROOM,
            'title' => 'General',
            'community_id' => $community->id,
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/rooms/{$room->id}/join")
            ->assertOk()
            ->assertJsonPath('members_count', 1);

        $this->assertTrue($room->isParticipant($member));
    }

    public function test_non_member_cannot_join_room(): void
    {
        $owner = User::factory()->create();
        $community = $this->createCommunity($owner);
        $outsider = User::factory()->create();

        $room = Conversation::create([
            'type' => Conversation::TYPE_ROOM,
            'title' => 'General',
            'community_id' => $community->id,
        ]);

        Sanctum::actingAs($outsider);

        $this->postJson("/api/rooms/{$room->id}/join")->assertForbidden();
        $this->assertFalse($room->isParticipant($outsider));
    }

    public function test_member_can_leave_room_and_send_message(): void
    {
        $owner = User::factory()->create();
        $community = $this->createCommunity($owner);
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        $room = Conversation::create([
            'type' => Conversation::TYPE_ROOM,
            'title' => 'General',
            'community_id' => $community->id,
        ]);
        $room->users()->attach($member->id);

        Sanctum::actingAs($member);

        // Enviar mensaje en la sala usando el endpoint existente.
        $this->postJson("/api/conversations/{$room->id}/messages", [
            'content' => '¡Hola sala!',
        ])->assertCreated()
            ->assertJsonPath('data.content', '¡Hola sala!');

        // Salir de la sala.
        $this->postJson("/api/rooms/{$room->id}/leave")
            ->assertOk()
            ->assertJsonPath('members_count', 0);

        $this->assertFalse($room->isParticipant($member));
    }
}
