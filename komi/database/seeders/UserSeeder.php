<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'javier@komi.com'],
            [
                'name' => 'Javier Sarmiento',
                'username' => 'javier_sarmiento',
                'password' => Hash::make('password123'),
                'birth_date' => '2002-09-15',
                'bio' => 'Desarrollador de software y creador de Komi. Bienvenido a mi red social!',
                'theme_color' => 'deepPurple',
                'gender' => 'male',
                'is_verified' => true,
                'is_premium' => true,
                'is_global_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Usuario de Pruebas',
                'username' => 'test_user',
                'password' => Hash::make('password'),
                'birth_date' => '1995-04-12',
                'bio' => 'Cuenta de prueba para el feed interactivo de Komi.',
                'avatar' => 'https://api.dicebear.com/9.x/notionists/svg?seed=test-user&backgroundColor=6D28D9',
                'theme_color' => 'deepPurple',
                'gender' => 'unspecified',
                'is_verified' => true,
                'is_premium' => true,
                'is_global_admin' => false,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Crear 40 usuarios distribuidos en los ultimos 14 dias
        $userCount = 40;
        for ($i = 0; $i < $userCount; $i++) {
            $daysAgo = fake()->numberBetween(0, 13);
            $hoursAgo = fake()->numberBetween(0, 23);
            $createdAt = now()->subDays($daysAgo)->subHours($hoursAgo);

            User::factory()->create([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Usuarios con distintos statuses para el grafico users_by_status
        $statuses = ['suspended', 'suspended', 'banned', 'pending'];
        foreach ($statuses as $status) {
            $daysAgo = fake()->numberBetween(0, 13);
            $createdAt = now()->subDays($daysAgo);
            User::factory()->create([
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
