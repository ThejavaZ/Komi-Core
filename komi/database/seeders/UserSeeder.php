<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tu usuario de prueba (Para loguearte directo en Flutter)
        User::create([
            'name' => 'Javier Sarmiento',
            'username' => 'javier_sarmiento',
            'email' => 'javier@komi.com',
            'password' => Hash::make('password123'), // Contraseña fácil para pruebas
            'birth_date' => '2002-09-15', // Tu fecha simulada
            'bio' => 'Desarrollador de software y creador de Komi. ¡Bienvenido a mi red social!',
            'theme_color' => 'deepPurple',
            'gender' => 'male',
            'is_verified' => true,      // Tu cuenta ya viene verificada
            'is_premium' => true,       // Cuenta premium de creador
            'is_global_admin' => true,  // Administrador global
            'status' => 'active',       // Cuenta activa de inmediato sin verificar por correo
            'email_verified_at' => now(),
        ]);

        // 2. Aquí es donde agregaremos el Factory para crear usuarios bots más adelante...
    }
}
