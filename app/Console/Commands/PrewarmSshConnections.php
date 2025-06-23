<?php

namespace App\Console\Commands;

use App\Services\SshConnectionPoolService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PrewarmSshConnections extends Command
{
    protected $signature = 'sshm:prewarm {--force : Force pre-warming even if recently done}';

    protected $description = 'Pre-warm SSH connections for all active hosts to reduce latency';

    public function handle(SshConnectionPoolService $connectionPool): int
    {
        $this->info('🔥 Pre-warming SSH connections...');

        $startTime = microtime(true);
        $warmedCount = $connectionPool->preWarmConnections();
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($warmedCount > 0) {
            $this->info("✅ Pre-warmed {$warmedCount} SSH connections in {$executionTime}ms");
            $avgTime = round($executionTime / $warmedCount, 2);
            $this->line("   Average time per connection: {$avgTime}ms");
        } else {
            $this->warn('⚠️  No connections were pre-warmed');
        }

        // Display connection pool status
        $stats = $connectionPool->getConnectionStats();
        $this->newLine();
        $this->line('📊 Connection Pool Status:');
        $this->line("   Active connections: {$stats['active_connections']}");
        $this->line("   Total connections created: {$stats['total_connections']}");

        Log::info('SSH connections pre-warmed', [
            'warmed_count' => $warmedCount,
            'execution_time_ms' => $executionTime,
            'avg_time_ms' => $warmedCount > 0 ? $executionTime / $warmedCount : 0,
        ]);

        return self::SUCCESS;
    }
}
