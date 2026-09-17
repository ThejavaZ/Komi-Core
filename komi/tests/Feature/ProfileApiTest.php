<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_requires_authentication(): void
    {
        $this->putJson('/api/user/profile', ['name' => 'X'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_update_his_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Antiguo',
            'username' => 'viejo_usuario',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', [
            'name' => 'Nuevo Nombre',
            'username' => 'nuevo_usuario',
            'bio' => 'Hola, soy Komi!',
            'avatar' => 'https://example.com/avatar.png',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Nuevo Nombre')
            ->assertJsonPath('user.username', 'nuevo_usuario')
            ->assertJsonPath('user.bio', 'Hola, soy Komi!')
            ->assertJsonPath('user.avatar', 'https://example.com/avatar.png');
    }

    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'tomado']);
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', ['username' => 'tomado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_partial_update_only_changes_provided_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Fulano',
            'bio' => 'Bio original',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/user/profile', ['bio' => 'Bio nueva'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Fulano')
            ->assertJsonPath('user.bio', 'Bio nueva');
    }
}
