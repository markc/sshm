<?php

namespace App\Providers;

use App\Settings\SshSettings;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SshSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SshSettings::class, function ($app) {
            // Get settings from database if possible
            try {
                if (Schema::hasTable('settings')) {
                    $settings = DB::table('settings')
                        ->where('group', 'ssh')
                        ->get();
                    
                    if ($settings->count() > 0) {
                        $settingsData = [];
                        
                        foreach ($settings as $setting) {
                            try {
                                $settingsData[$setting->name] = json_decode($setting->value, true);
                            } catch (\Exception $e) {
                                $settingsData[$setting->name] = $setting->value;
                            }
                        }
                        
                        // Merge with env defaults for any missing settings
                        return new SshSettings([
                            'home_dir' => $settingsData['home_dir'] ?? $_SERVER['HOME'] ?? env('SSH_HOME_DIR', '/home/user'),
                            'default_user' => $settingsData['default_user'] ?? env('SSH_DEFAULT_USER', 'root'),
                            'default_port' => $settingsData['default_port'] ?? (int)env('SSH_DEFAULT_PORT', 22),
                            'default_key_type' => $settingsData['default_key_type'] ?? env('SSH_DEFAULT_KEY_TYPE', 'ed25519'),
                            'strict_host_checking' => $settingsData['strict_host_checking'] ?? (bool)env('SSH_STRICT_HOST_CHECKING', false),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Fallback to environment variables if database error
                report($e);
            }

            // If no settings in database or error, use defaults from environment
            return new SshSettings([
                'home_dir' => $_SERVER['HOME'] ?? env('SSH_HOME_DIR', '/home/user'),
                'default_user' => env('SSH_DEFAULT_USER', 'root'),
                'default_port' => (int)env('SSH_DEFAULT_PORT', 22),
                'default_key_type' => env('SSH_DEFAULT_KEY_TYPE', 'ed25519'),
                'strict_host_checking' => (bool)env('SSH_STRICT_HOST_CHECKING', false),
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Create settings table if it doesn't exist
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('group')->index();
                $table->string('name');
                $table->text('value')->nullable();
                $table->timestamps();
                
                $table->unique(['group', 'name']);
            });
        }
    }
}