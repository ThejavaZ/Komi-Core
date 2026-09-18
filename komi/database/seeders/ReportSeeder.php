<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = User::query()->where('status', 'active')->get();
        $posts = Post::query()->whereNull('deleted_at')->get();

        if ($users->isEmpty() || $posts->isEmpty()) {
            return;
        }

        $reasons = [
            'Spam',
            'Contenido ofensivo',
            'Acoso',
            'Informacion falsa',
            'Contenido inapropiado',
            'Robo de contenido',
            'Fuera de contexto',
        ];

        $statuses = ['pending', 'pending', 'pending', 'resolved', 'dismissed'];

        for ($i = 0; $i < 25; $i++) {
            $daysAgo = fake()->numberBetween(0, 13);
            $createdAt = now()->subDays($daysAgo);

            Report::create([
                'user_id' => $users->random()->id,
                'reportable_type' => Post::class,
                'reportable_id' => $posts->random()->id,
                'reason' => fake()->randomElement($reasons),
                'description' => fake()->optional(0.7)->sentence(),
                'status' => fake()->randomElement($statuses),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
