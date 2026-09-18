<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Username "limpio": slug sin espacios ni caracteres raros.
        $username = Str::slug(fake()->unique()->userName());
        $avatarSeed = $username.'-'.mt_rand(100, 999);

        return [
            'name' => fake()->name(),
            'username' => $username,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            // Perfil enriquecido para el feed de Flutter
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'bio' => fake()->optional(0.8)->sentence(), // 80% trae biografía
            'avatar' => fake()->optional(0.9)->randomElement([
                "https://api.dicebear.com/9.x/notionists/svg?seed={$avatarSeed}&backgroundColor=6D28D9",
                "https://api.dicebear.com/9.x/avataaars/svg?seed={$avatarSeed}&backgroundColor=6D28D9",
                "https://api.dicebear.com/9.x/fun-emoji/svg?seed={$avatarSeed}",
                "https://api.dicebear.com/9.x/bottts/svg?seed={$avatarSeed}",
                "https://api.dicebear.com/9.x/pixel-art/svg?seed={$avatarSeed}",
            ]),
            'banner' => fake()->optional(0.4)->randomElement([
                'https://picsum.photos/seed/banner-'.$username.'/1200/400',
                'https://picsum.photos/seed/banner2-'.$username.'/1200/400',
            ]),
            'theme_color' => fake()->randomElement([
                'deepPurple', 'indigo', 'blue', 'teal', 'green', 'orange', 'pink', 'red',
            ]),
            'gender' => fake()->randomElement(['male', 'female', 'unspecified']),
            'is_verified' => fake()->boolean(15), // 15% son cuentas verificadas
            'is_premium' => fake()->boolean(10),
            'is_global_admin' => fake()->boolean(2),
            // Todas activas por defecto para poblar el feed de prueba.
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Fija un usuario "conocido" con credenciales estables para pruebas rápidas
     * desde Flutter (test@example.com / password).
     */
    public function knownTestUser(): static
    {
        return $this->state(fn () => [
            'name' => 'Usuario de Pruebas',
            'username' => 'test_user',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'avatar' => 'https://api.dicebear.com/9.x/notionists/svg?seed=test-user&backgroundColor=6D28D9',
            'is_verified' => true,
            'is_premium' => true,
            'status' => 'active',
        ]);
    }
}