<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Construye un usuario simulado de Socialite.
     */
    private function fakeSocialiteUser(array $attributes = []): SocialiteUser
    {
        $socialiteUser = new SocialiteUser;

        $socialiteUser->id = $attributes['id'] ?? 'google-123456';
        $socialiteUser->name = $attributes['name'] ?? 'Ana Pérez';
        $socialiteUser->email = $attributes['email'] ?? 'ana.perez@example.com';
        $socialiteUser->avatar = $attributes['avatar'] ?? 'https://lh3.googleusercontent.com/avatar/123';

        return $socialiteUser;
    }

    /**
     * Mockea el driver de Socialite para que userFromToken devuelva $response
     * o lance la excepción indicada.
     */
    private function mockSocialiteDriver(string $provider, mixed $socialiteResult): void
    {
        $driverMock = Mockery::mock(SocialiteProvider::class);

        if ($socialiteResult instanceof \Throwable) {
            $driverMock->shouldReceive('userFromToken')
                ->once()
                ->andThrow($socialiteResult);
        } else {
            $driverMock->shouldReceive('userFromToken')
                ->once()
                ->andReturn($socialiteResult);
        }

        Socialite::shouldReceive('driver')
            ->with($provider)
            ->once()
            ->andReturn($driverMock);
    }

    public function test_social_login_requires_valid_provider_and_token(): void
    {
        $this->postJson('/api/auth/social-login', [
            'provider' => 'linkedin',
            'token' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['provider', 'token']);
    }

    public function test_user_is_created_on_first_social_login(): void
    {
        $this->mockSocialiteDriver('google', $this->fakeSocialiteUser());

        $response = $this->postJson('/api/auth/social-login', [
            'provider' => 'google',
            'token' => 'oauth-access-token-válido',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('user.name', 'Ana Pérez')
            ->assertJsonPath('user.email', 'ana.perez@example.com')
            ->assertJsonStructure(['token', 'user' => ['id', 'username', 'email', 'avatar']]);

        $this->assertDatabaseHas('users', [
            'email' => 'ana.perez@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123456',
            'status' => 'active',
        ]);

        $user = User::where('email', 'ana.perez@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
        $this->assertEquals('anaperez', $user->username);
    }

    public function test_existing_user_by_provider_id_logs_in_without_duplicating(): void
    {
        $existing = User::factory()->create([
            'name' => 'Ana Pérez',
            'username' => 'ana_perez',
            'email' => 'ana.perez@example.com',
            'provider' => 'google',
            'provider_id' => 'google-123456',
            'status' => 'active',
        ]);

        $this->mockSocialiteDriver('facebook', $this->fakeSocialiteUser([
            'id' => 'facebook-999',
            'email' => 'ana.perez@example.com',
        ]));

        // Aunque cambiemos de proveedor, el vínculo por provider_id gana.
        $response = $this->postJson('/api/auth/social-login', [
            'provider' => 'facebook',
            'token' => 'otro-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $existing->id)
            ->assertJsonPath('user.email', 'ana.perez@example.com');

        $this->assertSame(
            User::where('email', 'ana.perez@example.com')->count(),
            1,
        );
    }

    public function test_existing_user_by_email_gets_linked_to_provider(): void
    {
        $existing = User::factory()->create([
            'name' => 'Ana Pérez',
            'username' => 'ana_perez',
            'email' => 'ana.perez@example.com',
            'status' => 'active',
        ]);

        $this->mockSocialiteDriver('google', $this->fakeSocialiteUser());

        $response = $this->postJson('/api/auth/social-login', [
            'provider' => 'google',
            'token' => 'token-google',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.id', $existing->id)
            ->assertJsonPath('user.email', 'ana.perez@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'provider' => 'google',
            'provider_id' => 'google-123456',
        ]);
    }

    public function test_username_is_unique_when_social_email_collides(): void
    {
        User::factory()->create([
            'username' => 'anaperez',
            'email' => 'otro.correo@example.com',
        ]);

        $this->mockSocialiteDriver('google', $this->fakeSocialiteUser());

        $response = $this->postJson('/api/auth/social-login', [
            'provider' => 'google',
            'token' => 'token-google',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'ana.perez@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'ana.perez@example.com',
            'username' => 'anaperez1',
        ]);
    }

    public function test_invalid_token_returns_401(): void
    {
        // Socialite lanza una excepción genérica al rechazar un token inválido
        // (por ejemplo, al validar el JWT de Google o el token de Facebook).
        $this->mockSocialiteDriver('google', new \Exception('Token inválido o caducado'));

        $this->postJson('/api/auth/social-login', [
            'provider' => 'google',
            'token' => 'token-no-valido',
        ])->assertUnauthorized()
            ->assertJsonPath('status', 'error');
    }

    public function test_banned_user_cannot_login_via_social(): void
    {
        User::factory()->create([
            'name' => 'Malo',
            'username' => 'malo_baneado',
            'email' => 'malo@example.com',
            'provider' => 'google',
            'provider_id' => 'google-666',
            'status' => 'banned',
        ]);

        $this->mockSocialiteDriver('google', $this->fakeSocialiteUser([
            'id' => 'google-666',
            'email' => 'malo@example.com',
        ]));

        $this->postJson('/api/auth/social-login', [
            'provider' => 'google',
            'token' => 'token-malo',
        ])->assertForbidden();
    }
}
