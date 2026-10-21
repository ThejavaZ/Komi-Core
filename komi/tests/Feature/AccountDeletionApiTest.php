<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountDeletionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Javier',
            'bio' => 'Hola soy Javier',
        ]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/user/account')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $user->refresh();
        $this->assertTrue($user->trashed());
        $this->assertSame('Usuario eliminado', $user->name);
        $this->assertSame('user_deleted_' . $user->id, $user->username);
        $this->assertNull($user->bio);
        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_session_token_is_invalid_after_deletion(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken('komi_auth_token')->plainTextToken;

        $this->withToken($plainToken)
            ->deleteJson('/api/user/account')
            ->assertOk();

        // El guard de Sanctum memoriza al usuario entre requests en el mismo test.
        $this->app['auth']->forgetGuards();

        $this->withToken($plainToken)
            ->getJson('/api/me/bookmarks')
            ->assertUnauthorized();
    }

    public function test_deletion_requires_authentication(): void
    {
        $this->deleteJson('/api/user/account')->assertUnauthorized();
    }
}
