<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateDesktopUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sshm:create-desktop-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update the desktop mode user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = config('app.desktop_user_email', 'desktop@sshm.local');
        $name = config('app.desktop_user_name', 'Desktop User');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt(Str::random(32)), // Random password since it won't be used
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->info('Desktop user created successfully:');
        } else {
            $this->info('Desktop user already exists:');
        }

        $this->line("  Name: {$user->name}");
        $this->line("  Email: {$user->email}");

        return Command::SUCCESS;
    }
}
