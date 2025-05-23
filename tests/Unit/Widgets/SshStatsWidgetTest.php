<?php

use App\Filament\Widgets\SshStatsWidget;
use App\Models\SshHost;
use App\Models\SshKey;

describe('SshStatsWidget', function () {
    beforeEach(function () {
        $this->widget = new SshStatsWidget();

        // Create test data
        SshHost::factory()->count(3)->create(['active' => true]);
        SshHost::factory()->count(2)->create(['active' => false]);

        SshKey::factory()->count(5)->create(['active' => true]);
        SshKey::factory()->count(1)->create(['active' => false]);
    });

    it('can instantiate the widget', function () {
        expect($this->widget)->toBeInstanceOf(SshStatsWidget::class);
    });

    it('has correct sort order', function () {
        expect($this->widget::getSort())->toBe(2);
    });

    it('returns correct stats structure', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);

        expect($stats)->toBeArray()
            ->and($stats)->toHaveCount(3);
    });

    it('calculates SSH host statistics correctly', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);

        $hostStat = collect($stats)->firstWhere(fn ($stat) => str_contains($stat->getLabel(), 'SSH Hosts'));

        expect($hostStat)->not->toBeNull();
        expect($hostStat->getValue())->toBe(5); // 3 active + 2 inactive
    });

    it('calculates SSH key statistics correctly', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);

        $keyStat = collect($stats)->firstWhere(fn ($stat) => str_contains($stat->getLabel(), 'SSH Keys'));

        expect($keyStat)->not->toBeNull();
        expect($keyStat->getValue())->toBe(6); // 5 active + 1 inactive
    });

    it('includes system version information', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($this->widget);

        $versionStat = collect($stats)->firstWhere(fn ($stat) => str_contains($stat->getLabel(), 'System Versions'));

        expect($versionStat)->not->toBeNull();
    });

    it('can get package versions', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getPackageVersion');
        $method->setAccessible(true);

        $version = $method->invoke($this->widget, 'laravel/framework');
        expect($version)->toBeString()
            ->and($version)->not->toBe('Unknown');
    });

    it('handles unknown packages gracefully', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getPackageVersion');
        $method->setAccessible(true);

        $version = $method->invoke($this->widget, 'non/existent-package');
        expect($version)->toBe('Unknown');
    });

    it('updates stats when data changes', function () {
        $reflection = new ReflectionClass($this->widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);

        $initialStats = $method->invoke($this->widget);
        $initialHostStat = collect($initialStats)->firstWhere(fn ($stat) => str_contains($stat->getLabel(), 'SSH Hosts'));
        $initialCount = $initialHostStat->getValue();

        // Add more hosts
        SshHost::factory()->count(2)->create(['active' => true]);

        // Create new widget instance to get fresh data
        $newWidget = new SshStatsWidget();
        $newReflection = new ReflectionClass($newWidget);
        $newMethod = $newReflection->getMethod('getStats');
        $newMethod->setAccessible(true);
        $newStats = $newMethod->invoke($newWidget);
        $newHostStat = collect($newStats)->firstWhere(fn ($stat) => str_contains($stat->getLabel(), 'SSH Hosts'));

        expect($newHostStat->getValue())->toBe($initialCount + 2);
    });

    it('handles empty database gracefully', function () {
        // Clear all data
        SshHost::query()->delete();
        SshKey::query()->delete();

        $widget = new SshStatsWidget();
        $reflection = new ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        expect($stats)->toBeArray()
            ->and($stats)->toHaveCount(3);

        $hostStat = collect($stats)->firstWhere(fn ($stat) => str_contains($stat->getLabel(), 'SSH Hosts'));
        $keyStat = collect($stats)->firstWhere(fn ($stat) => str_contains($stat->getLabel(), 'SSH Keys'));

        expect($hostStat->getValue())->toBe(0);
        expect($keyStat->getValue())->toBe(0);
    });
});
