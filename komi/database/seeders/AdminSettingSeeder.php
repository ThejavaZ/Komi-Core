<?php

namespace Database\Seeders;

use App\Models\AdminSetting;
use Illuminate\Database\Seeder;

class AdminSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'platform_name' => 'Komi',
            'platform_description' => 'Red social para comunidades',
            'max_posts_per_day' => 50,
            'max_chars_per_post' => 5000,
            'registration_enabled' => true,
            'maintenance_mode' => false,
            'rate_limit_login' => 5,
            'rate_limit_api' => 60,
            'rate_limit_upload' => 10,
        ];

        foreach ($defaults as $key => $value) {
            AdminSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
