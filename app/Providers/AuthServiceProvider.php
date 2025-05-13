<?php

namespace App\Providers;

use App\Filament\Pages\SshCommandRunner;
use App\Models\SshConfig;
use App\Policies\SshCommandRunnerPolicy;
use App\Policies\SshConfigPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        SshConfig::class => SshConfigPolicy::class,
        SshCommandRunner::class => SshCommandRunnerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
