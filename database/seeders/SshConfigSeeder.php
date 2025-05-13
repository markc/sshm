<?php

namespace Database\Seeders;

use App\Models\SshConfig;
use Illuminate\Database\Seeder;

class SshConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default SSH configuration using .env values if available
        SshConfig::create([
            'name' => 'Default Connection',
            'host' => env('SSH_DEFAULT_HOST', '127.0.0.1'),
            'port' => env('SSH_DEFAULT_PORT', 22),
            'username' => env('SSH_DEFAULT_USERNAME', 'user'),
            'private_key_path' => env('SSH_DEFAULT_PRIVATE_KEY_PATH'),
            'password' => env('SSH_DEFAULT_PASSWORD'),
            'is_default' => true,
        ]);
    }
}
