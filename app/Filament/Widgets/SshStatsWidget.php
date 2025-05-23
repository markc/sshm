<?php

namespace App\Filament\Widgets;

use App\Models\SshHost;
use App\Models\SshKey;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SshStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Get system versions
        $laravelVersion = app()->version();
        $filamentVersion = $this->getPackageVersion('filament/filament');
        $spatieVersion = $this->getPackageVersion('spatie/ssh');
        $systemDescription = "Laravel v{$laravelVersion}" . PHP_EOL . "Filament v{$filamentVersion}" . PHP_EOL . "Spatie SSH v{$spatieVersion}";

        // Get SSH stats
        $totalHosts = SshHost::count();
        $activeHosts = SshHost::where('active', true)->count();
        $inactiveHosts = $totalHosts - $activeHosts;

        $totalKeys = SshKey::count();
        $activeKeys = SshKey::where('active', true)->count();
        $inactiveKeys = $totalKeys - $activeKeys;

        return [
            Stat::make('System Versions', '')
                ->description(view('filament.widgets.system-versions-description', [
                    'laravel' => $laravelVersion,
                    'filament' => $filamentVersion,
                    'spatie' => $spatieVersion,
                ]))
                ->color('info'),

            Stat::make('SSH Hosts', $totalHosts)
                ->description($activeHosts . ' active, ' . $inactiveHosts . ' inactive')
                ->descriptionIcon('heroicon-m-server')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('SSH Keys', $totalKeys)
                ->description($activeKeys . ' active, ' . $inactiveKeys . ' inactive')
                ->descriptionIcon('heroicon-m-key')
                ->color('success')
                ->chart([2, 10, 5, 22, 15, 10, 25]),
        ];
    }

    private function getPackageVersion(string $package): string
    {
        try {
            if (\Composer\InstalledVersions::isInstalled($package)) {
                $version = \Composer\InstalledVersions::getVersion($package);

                // Remove 'v' prefix and any dev/beta suffixes for cleaner display
                return preg_replace('/^v?(\d+\.\d+\.\d+).*/', '$1', $version) ?: 'Unknown';
            }
        } catch (\Exception $e) {
            // Fallback if composer metadata is not available
        }

        return 'Unknown';
    }
}
