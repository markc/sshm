<?php

namespace App\Console\Commands;

use App\Services\SshConnectionPoolService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MonitorPerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sshm:monitor {--clear : Clear all performance stats}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor SSHM performance metrics and connection pool stats';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('clear')) {
            $this->clearStats();

            return 0;
        }

        $this->displayPerformanceStats();

        return 0;
    }

    private function clearStats(): void
    {
        $this->info('🧹 Clearing all performance statistics...');

        // Clear Redis-based stats
        $keys = Redis::keys('sshm:*');
        if (! empty($keys)) {
            Redis::del($keys);
            $this->info('   ✅ Cleared ' . count($keys) . ' Redis keys');
        }

        // Clear connection pool
        $connectionPool = app(SshConnectionPoolService::class);
        $connectionPool->closeAllConnections();
        $this->info('   ✅ Closed all SSH connections');

        $this->info('📊 Performance statistics cleared');
    }

    private function displayPerformanceStats(): void
    {
        $this->info('🚀 SSHM Performance Monitor');
        $this->info('==========================');
        $this->newLine();

        // Worker Status
        $this->displayWorkerStatus();
        $this->newLine();

        // Connection Pool Stats
        $this->displayConnectionPoolStats();
        $this->newLine();

        // Redis Performance
        $this->displayRedisStats();
        $this->newLine();

        // Database Performance
        $this->displayDatabaseStats();
        $this->newLine();

        // Cache Stats
        $this->displayCacheStats();
    }

    private function displayWorkerStatus(): void
    {
        $this->info('🔧 FrankenPHP Server Status:');

        // Check if FrankenPHP is running
        $processes = shell_exec('pgrep -f frankenphp');
        $isRunning = ! empty(trim($processes ?? ''));

        if ($isRunning) {
            $processList = array_filter(explode("\n", trim($processes)));
            $processCount = count($processList);

            // Get process info
            $pid = $processList[0] ?? 'N/A';
            $memoryUsage = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);

            $this->line('   Status: <fg=green>Running</fg=green>');
            $this->line("   Process Count: <fg=green>{$processCount}</fg=green>");
            $this->line("   Main PID: <fg=blue>{$pid}</fg=blue>");
            $this->line('   Memory Usage: <fg=yellow>' . $this->formatBytes($memoryUsage) . '</fg=yellow>');
            $this->line('   Peak Memory: <fg=yellow>' . $this->formatBytes($peakMemory) . '</fg=yellow>');
            $this->line('   Laravel Version: <fg=blue>' . app()->version() . '</fg=blue>');

            // Check for FrankenPHP threads from logs
            $threadsInfo = shell_exec('pgrep -f frankenphp | wc -l');
            if ($threadsInfo) {
                $this->line('   Thread Count: <fg=green>' . trim($threadsInfo) . '</fg=green>');
            }
        } else {
            $this->line('   Status: <fg=red>Not Running</fg=red>');
        }
    }

    private function displayConnectionPoolStats(): void
    {
        $this->info('🔗 SSH Connection Pool:');

        $poolStats = Redis::get('sshm:connection_pool_stats');
        if ($poolStats) {
            $stats = json_decode($poolStats, true);

            $this->line('   Active Connections: ' . count($stats['stats']['connections']));
            $this->line("   Requests Processed: {$stats['requests_processed']}");
            $this->line('   Memory Usage: ' . round($stats['memory_usage'] / 1024 / 1024, 2) . 'MB');
            $this->line('   Peak Memory: ' . round($stats['peak_memory'] / 1024 / 1024, 2) . 'MB');
            $this->line('   Last Updated: ' . date('Y-m-d H:i:s', $stats['timestamp']));

            if (! empty($stats['stats']['connections'])) {
                $this->line('   Connection Details:');
                foreach ($stats['stats']['connections'] as $connection) {
                    $age = time() - $connection['created_at'];
                    $this->line("     • {$connection['host']} (Age: {$age}s, Uses: {$connection['connection_count']})");
                }
            }
        } else {
            $this->line('   Status: <fg=yellow>No stats available</fg=yellow>');
        }
    }

    private function displayRedisStats(): void
    {
        $this->info('⚡ Redis Performance:');

        try {
            // Test Redis connection first
            Redis::ping();

            $this->line('   Status: <fg=green>Connected</fg=green>');

            try {
                $redisInfo = Redis::info();

                // Redis info can have different formats, handle safely
                $usedMemory = $redisInfo['used_memory'] ?? $redisInfo['Memory']['used_memory'] ?? 'N/A';
                $connectedClients = $redisInfo['connected_clients'] ?? $redisInfo['Clients']['connected_clients'] ?? 'N/A';
                $totalCommands = $redisInfo['total_commands_processed'] ?? $redisInfo['Stats']['total_commands_processed'] ?? 'N/A';
                $hits = $redisInfo['keyspace_hits'] ?? $redisInfo['Stats']['keyspace_hits'] ?? 0;
                $misses = $redisInfo['keyspace_misses'] ?? $redisInfo['Stats']['keyspace_misses'] ?? 0;

                if ($usedMemory !== 'N/A' && is_numeric($usedMemory)) {
                    $this->line('   Used Memory: ' . $this->formatBytes($usedMemory));
                }
                if ($connectedClients !== 'N/A') {
                    $this->line("   Connected Clients: {$connectedClients}");
                }
                if ($totalCommands !== 'N/A') {
                    $this->line('   Total Commands: ' . number_format($totalCommands));
                }

                $this->line('   Keyspace Hits: ' . number_format($hits));
                $this->line('   Keyspace Misses: ' . number_format($misses));

                // Calculate hit ratio
                $total = $hits + $misses;
                $hitRatio = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
                $this->line("   Hit Ratio: {$hitRatio}%");

            } catch (\Exception $infoException) {
                $this->line('   Info: <fg=yellow>Limited (basic connection only)</fg=yellow>');
            }

            // Count SSHM-specific keys
            try {
                $sshmKeys = Redis::keys('sshm:*');
                $this->line('   SSHM Keys: ' . count($sshmKeys));
            } catch (\Exception $keysException) {
                $this->line('   SSHM Keys: <fg=yellow>Unable to count</fg=yellow>');
            }

        } catch (\Exception $e) {
            $this->line('   Status: <fg=red>Connection Failed</fg=red>');
            $this->line('   Error: ' . $e->getMessage());
        }
    }

    private function displayDatabaseStats(): void
    {
        $this->info('🗄️  Database Performance:');

        try {
            // Get database size
            $dbPath = database_path('database.sqlite');
            if (file_exists($dbPath)) {
                $dbSize = filesize($dbPath);
                $this->line('   Database Size: ' . $this->formatBytes($dbSize));
            }

            // Count records
            $hostCount = DB::table('ssh_hosts')->count();
            $keyCount = DB::table('ssh_keys')->count();
            $userCount = DB::table('users')->count();

            $this->line("   SSH Hosts: {$hostCount}");
            $this->line("   SSH Keys: {$keyCount}");
            $this->line("   Users: {$userCount}");

            // Check for indexes
            $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%'");
            $this->line('   Custom Indexes: ' . count($indexes));

        } catch (\Exception $e) {
            $this->line('   Status: <fg=red>Query Failed</fg=red>');
        }
    }

    private function displayCacheStats(): void
    {
        $this->info('💾 Command Cache:');

        $cacheKeys = Redis::keys('sshm:cmd_cache:*');
        $this->line('   Cached Commands: ' . count($cacheKeys));

        if (! empty($cacheKeys)) {
            $totalSize = 0;
            $oldestCache = time();
            $newestCache = 0;

            foreach ($cacheKeys as $key) {
                $data = Redis::get($key);
                if ($data) {
                    $totalSize += strlen($data);
                    $cached = json_decode($data, true);
                    $timestamp = strtotime($cached['timestamp'] ?? '');
                    $oldestCache = min($oldestCache, $timestamp);
                    $newestCache = max($newestCache, $timestamp);
                }
            }

            $this->line('   Cache Size: ' . $this->formatBytes($totalSize));
            $this->line('   Oldest Entry: ' . date('Y-m-d H:i:s', $oldestCache));
            $this->line('   Newest Entry: ' . date('Y-m-d H:i:s', $newestCache));
        }
    }

    private function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m {$seconds}s";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m {$seconds}s";
        } elseif ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        } else {
            return "{$seconds}s";
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
