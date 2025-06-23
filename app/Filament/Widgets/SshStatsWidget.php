<?php

namespace App\Filament\Widgets;

use App\Models\SshHost;
use App\Models\SshKey;
use Composer\InstalledVersions;
use Exception;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class SshStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Cache system versions (rarely change) for 1 hour
        $systemVersions = Cache::remember('widget_system_versions', 3600, function () {
            return [
                'laravel' => app()->version(),
                'filament' => $this->getPackageVersion('filament/filament'),
                'spatie' => $this->getPackageVersion('spatie/ssh'),
            ];
        });

        // Cache SSH stats for 30 seconds to reduce database load
        $sshStats = Cache::remember('widget_ssh_stats', 30, function () {
            return [
                'total_hosts' => SshHost::count(),
                'active_hosts' => SshHost::where('active', true)->count(),
                'total_keys' => SshKey::count(),
                'active_keys' => SshKey::where('active', true)->count(),
            ];
        });

        // Extract cached values
        $totalHosts = $sshStats['total_hosts'];
        $activeHosts = $sshStats['active_hosts'];
        $inactiveHosts = $totalHosts - $activeHosts;

        $totalKeys = $sshStats['total_keys'];
        $activeKeys = $sshStats['active_keys'];
        $inactiveKeys = $totalKeys - $activeKeys;

        // Get performance metrics from Redis
        $cacheHits = Redis::hget('sshm:cache_stats', 'cache_hits') ?? 0;
        $connectionCount = Redis::hget('sshm:connection_stats', 'prewarm_success') ?? 0;

        return [
            Stat::make('System Versions', '')
                ->description(view('filament.widgets.system-versions-description', [
                    'laravel' => $systemVersions['laravel'],
                    'filament' => $systemVersions['filament'],
                    'spatie' => $systemVersions['spatie'],
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
            if (InstalledVersions::isInstalled($package)) {
                $version = InstalledVersions::getVersion($package);

                // Remove 'v' prefix and any dev/beta suffixes for cleaner display
                return preg_replace('/^v?(\d+\.\d+\.\d+).*/', '$1', $version) ?: 'Unknown';
            }
        } catch (Exception $e) {
            // Fallback if composer metadata is not available
        }

        return 'Unknown';
    }
}
