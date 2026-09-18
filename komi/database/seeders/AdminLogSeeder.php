<?php

namespace Database\Seeders;

use App\Models\AdminLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminLogSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::where('is_global_admin', true)->first();
        if (!$admin) {
            return;
        }

        $users = User::where('id', '!=', $admin->id)->get();
        $posts = Post::all();
        $reports = Report::all();

        $actions = [
            'user.update' => ['target' => 'user', 'collection' => null],
            'user.warn' => ['target' => 'user', 'collection' => null],
            'post.delete' => ['target' => 'post', 'collection' => null],
            'report.resolve' => ['target' => 'report', 'collection' => null],
        ];

        for ($i = 0; $i < 20; $i++) {
            $daysAgo = fake()->numberBetween(0, 13);
            $createdAt = now()->subDays($daysAgo)->subHours(fake()->numberBetween(0, 23));

            $action = fake()->randomElement(array_keys($actions));
            $targetType = match ($action) {
                'user.update', 'user.warn' => User::class,
                'post.delete' => Post::class,
                'report.resolve' => Report::class,
            };

            $target = match ($action) {
                'user.update', 'user.warn' => $users->random(),
                'post.delete' => $posts->random(),
                'report.resolve' => $reports->random(),
            };

            AdminLog::create([
                'admin_id' => $admin->id,
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $target->id,
                'old_values' => fake()->optional(0.7)->passthrough(['status' => fake()->randomElement(['active', 'pending'])]),
                'new_values' => fake()->optional(0.7)->passthrough(['status' => 'active']),
                'ip_address' => fake()->ipv4(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
