<?php

namespace App\Providers;

use App\Models\SshConfig;
use App\Observers\SshConfigObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        SshConfig::observe(SshConfigObserver::class);
    }
}
