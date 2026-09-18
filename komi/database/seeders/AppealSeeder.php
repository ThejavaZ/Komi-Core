<?php

namespace Database\Seeders;

use App\Models\Appeal;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppealSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $bannedUsers = User::whereIn('status', ['banned', 'suspended'])->get();
        $admin = User::where('is_global_admin', true)->first();

        if ($bannedUsers->isEmpty()) {
            return;
        }

        $reasons = [
            'No era consciente de las reglas de la comunidad.',
            'Creo que fue un malentendido, puedo explicar.',
            'Mi cuenta fue hackeada, yo no publique ese contenido.',
            'Pido una segunda revisión por favor.',
            'Ya corregí el problema, quiero volver.',
        ];

        $statuses = ['pending', 'pending', 'approved', 'rejected'];

        foreach ($bannedUsers as $user) {
            $daysAgo = fake()->numberBetween(0, 7);
            $createdAt = now()->subDays($daysAgo);
            $status = fake()->randomElement($statuses);

            Appeal::create([
                'user_id' => $user->id,
                'type' => $user->status === 'banned' ? 'ban' : 'suspension',
                'reason' => fake()->randomElement($reasons),
                'status' => $status,
                'admin_notes' => $status !== 'pending' ? fake()->sentence() : null,
                'reviewed_by' => $status !== 'pending' ? $admin?->id : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
