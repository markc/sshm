<?php

namespace App\Providers;

use App\Services\SshConnectionPoolService;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // SshSettings are registered in SshSettingsServiceProvider

        // Register SSH Connection Pool Service as singleton for optimal performance
        $this->app->singleton(SshConnectionPoolService::class, function ($app) {
            $service = new SshConnectionPoolService();

            // Configure connection pool based on environment
            $service->setMaxConnections(config('ssh.max_connections', 20));
            $service->setConnectionTimeout(config('ssh.connection_timeout', 300));

            return $service;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament.admin.custom-theme')->render(),
        );
    }
}
