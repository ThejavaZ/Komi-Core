<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityChallengeApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Retos',
            'slug' => 'comunidad-retos-' . uniqid(),
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_staff_can_create_challenge(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/challenges", [
            'title' => 'Reto de la semana',
            'description' => 'Publica 3 posts creativos',
            'deadline' => now()->addDays(7)->toIso8601String(),
        ])
            ->assertStatus(201)
            ->assertJsonPath('challenge.title', 'Reto de la semana')
            ->assertJsonPath('challenge.is_completed', false);

        $this->assertDatabaseHas('challenges', [
            'community_id' => $community->id,
            'user_id' => $owner->id,
            'title' => 'Reto de la semana',
        ]);
    }

    public function test_member_cannot_create_challenge(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($member);

        $this->postJson("/api/communities/{$community->id}/challenges", [
            'title' => 'Reto no autorizado',
            'deadline' => now()->addDay()->toIso8601String(),
        ])->assertStatus(403);
    }

    public function test_deadline_must_be_in_the_future(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/challenges", [
            'title' => 'Reto vencido',
            'deadline' => now()->subDay()->toIso8601String(),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('deadline');
    }

    public function test_user_can_join_and_leave_challenge(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $challenge = $community->challenges()->create([
            'user_id' => $owner->id,
            'title' => 'Reto unirse',
            'deadline' => now()->addDays(3),
        ]);

        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($member);

        $this->postJson("/api/communities/{$community->id}/challenges/{$challenge->id}/join")
            ->assertOk()
            ->assertJsonPath('participants_count', 1);

        $this->postJson("/api/communities/{$community->id}/challenges/{$challenge->id}/join")
            ->assertStatus(422);

        $this->postJson("/api/communities/{$community->id}/challenges/{$challenge->id}/leave")
            ->assertOk()
            ->assertJsonPath('participants_count', 0);

        $this->postJson("/api/communities/{$community->id}/challenges/{$challenge->id}/leave")
            ->assertStatus(422);
    }

    public function test_cannot_join_after_deadline_or_when_completed(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $past = $community->challenges()->create([
            'user_id' => $owner->id,
            'title' => 'Reto pasado',
            'deadline' => now()->subDay(),
        ]);
        $done = $community->challenges()->create([
            'user_id' => $owner->id,
            'title' => 'Reto completado',
            'deadline' => now()->addDay(),
            'is_completed' => true,
        ]);

        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        Sanctum::actingAs($member);

        $this->postJson("/api/communities/{$community->id}/challenges/{$past->id}/join")
            ->assertStatus(422);
        $this->postJson("/api/communities/{$community->id}/challenges/{$done->id}/join")
            ->assertStatus(422);
    }

    public function test_staff_can_mark_challenge_completed(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $challenge = $community->challenges()->create([
            'user_id' => $owner->id,
            'title' => 'Reto a completar',
            'deadline' => now()->addDays(2),
        ]);

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/challenges/{$challenge->id}", [
            'is_completed' => true,
        ])
            ->assertOk()
            ->assertJsonPath('challenge.is_completed', true);
    }

    public function test_list_shows_joined_flag_and_orders_by_deadline(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $later = $community->challenges()->create([
            'user_id' => $owner->id,
            'title' => 'Después',
            'deadline' => now()->addDays(9),
        ]);
        $sooner = $community->challenges()->create([
            'user_id' => $owner->id,
            'title' => 'Antes',
            'deadline' => now()->addDays(1),
        ]);
        $sooner->participants()->attach($owner->id);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/communities/{$community->id}/challenges")
            ->assertOk();

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Antes', $data[0]['title']);
        $this->assertTrue($data[0]['joined_by_me']);
        $this->assertEquals(1, $data[0]['participants_count']);
        $this->assertFalse($data[1]['joined_by_me']);
    }

    public function test_challenge_from_another_community_returns_404(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        [$otherOwner, $otherCommunity] = $this->createCommunityWithOwner();
        $foreign = $otherCommunity->challenges()->create([
            'user_id' => $otherOwner->id,
            'title' => 'Ajeno',
            'deadline' => now()->addDay(),
        ]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/communities/{$community->id}/challenges/{$foreign->id}/join")
            ->assertNotFound();
    }
}
