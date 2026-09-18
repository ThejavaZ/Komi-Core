<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TagSeeder::class,
            CommunitySeeder::class,
            CommunityUserSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            ReactionSeeder::class,
            ReportSeeder::class,
            AppealSeeder::class,
            AdminLogSeeder::class,
        ]);
    }
}
