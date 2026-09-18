<?php

namespace Database\Seeders;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommunityUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = User::query()->where('status', 'active')->get();
        $communities = Community::all();

        if ($users->isEmpty() || $communities->isEmpty()) {
            return;
        }

        foreach ($communities as $community) {
            $memberCount = fake()->numberBetween(5, min(20, $users->count()));
            $members = $users->random($memberCount);
            $community->members()->sync($members->pluck('id')->toArray());
        }
    }
}
