<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create default test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create desktop user if in desktop mode
        if (config('app.desktop_mode', false)) {
            User::factory()->create([
                'name' => config('app.desktop_user_name', 'Desktop User'),
                'email' => config('app.desktop_user_email', 'desktop@sshm.local'),
            ]);
        }
    }
}
