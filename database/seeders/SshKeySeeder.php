<?php

namespace Database\Seeders;

use App\Services\SshManagerService;
use Illuminate\Database\Seeder;

class SshKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sshManager = new SshManagerService;

        // Initialize the SSH directory structure if it doesn't exist
        try {
            $sshManager->initializeSshDirectory();
        } catch (\Exception $e) {
            $this->command->error('Failed to initialize SSH directory: '.$e->getMessage());

            return;
        }

        // Sync existing SSH keys from the filesystem
        try {
            $result = $sshManager->syncKeysWithDatabase();
            $this->command->info(
                "SSH keys synchronized: {$result['added']} added, {$result['updated']} updated, {$result['removed']} removed",
            );
        } catch (\Exception $e) {
            $this->command->error('Failed to sync SSH keys: '.$e->getMessage());
        }
    }
}
