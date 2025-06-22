<?php

namespace Tests;

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register a default panel for testing
        $panel = Panel::make()
            ->id('admin')
            ->path('admin')
            ->default();

        Filament::registerPanel($panel);
    }
}
