<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityRoleApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Roles',
            'slug' => 'comunidad-roles-' . uniqid(),
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_owner_can_promote_member_to_admin(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$member->id}/role", [
            'role' => 'admin',
        ])
            ->assertOk()
            ->assertJsonPath('member.role', 'admin')
            ->assertJsonPath('member.id', $member->id);

        $this->assertDatabaseHas('community_users', [
            'community_id' => $community->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);
    }

    public function test_plain_member_cannot_change_roles(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $other = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);
        $community->members()->attach($other->id, ['role' => 'member']);

        Sanctum::actingAs($member);

        $this->putJson("/api/communities/{$community->id}/members/{$other->id}/role", [
            'role' => 'admin',
        ])->assertStatus(403);
    }

    public function test_admin_can_promote_member_but_not_other_admin(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $otherAdmin = User::factory()->create();
        $community->members()->attach($admin->id, ['role' => 'admin']);
        $community->members()->attach($member->id, ['role' => 'member']);
        $community->members()->attach($otherAdmin->id, ['role' => 'admin']);

        Sanctum::actingAs($admin);

        $this->putJson("/api/communities/{$community->id}/members/{$member->id}/role", [
            'role' => 'admin',
        ])->assertOk();

        $this->putJson("/api/communities/{$community->id}/members/{$otherAdmin->id}/role", [
            'role' => 'member',
        ])->assertStatus(403);
    }

    public function test_owner_role_cannot_be_changed(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $admin = User::factory()->create();
        $community->members()->attach($admin->id, ['role' => 'admin']);

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$owner->id}/role", [
            'role' => 'member',
        ])->assertStatus(422);
    }

    public function test_target_must_be_a_member(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $outsider = User::factory()->create();

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$outsider->id}/role", [
            'role' => 'admin',
        ])->assertStatus(404);
    }

    public function test_invalid_role_value_is_rejected(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}/members/{$member->id}/role", [
            'role' => 'owner',
        ])->assertStatus(422)->assertJsonValidationErrors('role');
    }
}
