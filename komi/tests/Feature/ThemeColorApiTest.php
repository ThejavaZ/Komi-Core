<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ThemeColorApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCommunityWithOwner(): array
    {
        $owner = User::factory()->create();
        $community = Community::create([
            'name' => 'Comunidad Tema',
            'slug' => 'comunidad-tema',
            'owner_id' => $owner->id,
        ]);
        $community->members()->attach($owner->id, ['role' => 'owner']);

        return [$owner, $community];
    }

    public function test_user_can_update_theme_color(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', ['theme_color' => 'blue'])
            ->assertOk()
            ->assertJsonPath('user.theme_color', 'blue');

        $this->assertSame('blue', $user->fresh()->theme_color);
    }

    public function test_user_theme_color_must_be_a_valid_palette_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/user/profile', ['theme_color' => '#FF0000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('theme_color');
    }

    public function test_owner_can_set_community_theme_color(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->putJson("/api/communities/{$community->id}", ['theme_color' => 'crimson'])
            ->assertOk()
            ->assertJsonPath('community.theme_color', 'crimson');

        $this->assertSame('crimson', $community->fresh()->theme_color);
    }

    public function test_community_theme_color_is_exposed_in_show(): void
    {
        [$owner, $community] = $this->createCommunityWithOwner();

        Sanctum::actingAs($owner);

        $this->getJson("/api/communities/{$community->id}")
            ->assertOk()
            ->assertJsonPath('data.theme_color', 'deepPurple');
    }

    public function test_member_without_staff_role_cannot_set_theme_color(): void
    {
        [, $community] = $this->createCommunityWithOwner();
        $member = User::factory()->create();
        $community->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($member);

        $this->putJson("/api/communities/{$community->id}", ['theme_color' => 'blue'])
            ->assertStatus(403);
    }
}
