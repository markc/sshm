<?php

namespace Tests;

use App\Settings\SshSettings;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure default Filament panel for testing
        $panel = Panel::make()
            ->id('admin')
            ->path('/admin')
            ->default();

        Filament::registerPanel($panel);

        // Mock SshSettings for testing
        $this->app->singleton(SshSettings::class, function () {
            return new SshSettings([
                'home_dir' => '/tmp/test',
                'default_user' => 'testuser',
                'default_port' => 22,
                'default_key_type' => 'ed25519',
                'strict_host_checking' => false,
            ]);
        });
    }
}
