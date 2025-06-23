<?php

namespace App\Services;

use App\Models\SshHost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SshConnectionPoolService
{
    private array $connections = [];

    private int $maxConnections = 10;

    private int $connectionTimeout = 300; // 5 minutes

    /**
     * Get an SSH connection for the given host, reusing existing connections when possible
     * Enhanced with Redis persistence for worker mode
     */
    public function getConnection(SshHost $host): array
    {
        $connectionKey = $this->getConnectionKey($host);

        // Check Redis for persistent connection info first
        $redisKey = "sshm:connection:{$connectionKey}";
        $redisConnection = Redis::get($redisKey);

        if ($redisConnection) {
            $connectionData = json_decode($redisConnection, true);
            if ($this->isConnectionValidFromData($connectionData)) {
                $this->updateConnectionUsage($connectionKey);
                Redis::expire($redisKey, $this->connectionTimeout);
                Log::debug('Reusing Redis-cached SSH connection', ['host' => $host->name, 'key' => $connectionKey]);

                return $connectionData;
            } else {
                Redis::del($redisKey);
            }
        }

        // Check in-memory connections
        if (isset($this->connections[$connectionKey])) {
            $connection = $this->connections[$connectionKey];

            // Check if connection is still valid
            if ($this->isConnectionValid($connection)) {
                $this->updateConnectionUsage($connectionKey);
                Log::debug('Reusing in-memory SSH connection', ['host' => $host->name, 'key' => $connectionKey]);

                return $connection;
            } else {
                // Connection expired, remove it
                unset($this->connections[$connectionKey]);
                Redis::del($redisKey);
            }
        }

        // Create new connection
        $connection = $this->createConnection($host);
        $this->connections[$connectionKey] = $connection;

        // Store in Redis for persistence across worker requests
        Redis::setex($redisKey, $this->connectionTimeout, json_encode($connection));

        // Cleanup old connections if we have too many
        $this->cleanupConnections();

        Log::debug('Created new SSH connection with Redis persistence', ['host' => $host->name, 'key' => $connectionKey]);

        return $connection;
    }

    /**
     * Create a new SSH connection configuration
     */
    private function createConnection(SshHost $host): array
    {
        $controlPath = sys_get_temp_dir() . '/ssh-pool-' . md5($host->hostname . $host->user . $host->port);

        return [
            'host' => $host,
            'control_path' => $controlPath,
            'created_at' => time(),
            'last_used' => time(),
            'connection_count' => 0,
        ];
    }

    /**
     * Check if a connection is still valid
     */
    private function isConnectionValid(array $connection): bool
    {
        $age = time() - $connection['created_at'];

        return $age < $this->connectionTimeout && file_exists($connection['control_path']);
    }

    /**
     * Check if a Redis-cached connection is still valid
     */
    private function isConnectionValidFromData(array $connectionData): bool
    {
        if (! isset($connectionData['created_at'], $connectionData['control_path'])) {
            return false;
        }

        $age = time() - $connectionData['created_at'];

        return $age < $this->connectionTimeout && file_exists($connectionData['control_path']);
    }

    /**
     * Generate a unique connection key for a host
     */
    private function getConnectionKey(SshHost $host): string
    {
        return md5($host->hostname . ':' . $host->port . ':' . $host->user . ':' . ($host->identity_file ?? ''));
    }

    /**
     * Cleanup old or unused connections
     */
    private function cleanupConnections(): void
    {
        if (count($this->connections) <= $this->maxConnections) {
            return;
        }

        // Sort connections by last_used time
        uasort($this->connections, function ($a, $b) {
            return $a['last_used'] <=> $b['last_used'];
        });

        // Remove oldest connections
        while (count($this->connections) > $this->maxConnections) {
            $connectionKey = array_key_first($this->connections);
            $connection = $this->connections[$connectionKey];

            // Close the SSH control connection
            $this->closeConnection($connection);
            unset($this->connections[$connectionKey]);

            Log::debug('Cleaned up old SSH connection', ['key' => $connectionKey]);
        }
    }

    /**
     * Close an SSH connection
     */
    private function closeConnection(array $connection): void
    {
        if (file_exists($connection['control_path'])) {
            // Send exit command to close the control connection
            $host = $connection['host'];
            $command = sprintf(
                'ssh -o ControlPath=%s -O exit %s@%s 2>/dev/null',
                escapeshellarg($connection['control_path']),
                escapeshellarg($host->user),
                escapeshellarg($host->hostname)
            );

            exec($command);

            // Remove the control socket file if it still exists
            if (file_exists($connection['control_path'])) {
                @unlink($connection['control_path']);
            }
        }
    }

    /**
     * Get connection statistics for monitoring
     */
    public function getStats(): array
    {
        return [
            'active_connections' => count($this->connections),
            'max_connections' => $this->maxConnections,
            'connections' => array_map(function ($connection) {
                return [
                    'host' => $connection['host']->name,
                    'created_at' => $connection['created_at'],
                    'last_used' => $connection['last_used'],
                    'age' => time() - $connection['created_at'],
                    'connection_count' => $connection['connection_count'],
                ];
            }, $this->connections),
        ];
    }

    /**
     * Force close all connections (useful for cleanup)
     */
    public function closeAllConnections(): void
    {
        foreach ($this->connections as $connection) {
            $this->closeConnection($connection);
        }

        $this->connections = [];
        Log::info('Closed all SSH connections');
    }

    /**
     * Preload a connection for a host with keep-alive testing
     */
    public function preloadConnection(SshHost $host): void
    {
        $this->getConnection($host);

        // Test connection with a lightweight command
        $this->testConnection($host);
    }

    /**
     * Pre-warm connections for all active hosts
     */
    public function preWarmConnections(): int
    {
        $activeHosts = SshHost::where('is_active', true)->get();
        $warmedCount = 0;

        foreach ($activeHosts as $host) {
            try {
                $this->preloadConnection($host);
                $warmedCount++;

                // Store pre-warmed status in Redis
                $redisKey = 'sshm:prewarmed:' . $this->getConnectionKey($host);
                Redis::setex($redisKey, 300, time()); // 5 minutes

                Log::debug('Pre-warmed SSH connection', ['host' => $host->name]);
            } catch (\Exception $e) {
                Log::warning('Failed to pre-warm SSH connection', [
                    'host' => $host->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Track pre-warming statistics
        Redis::hincrby('sshm:connection_stats', 'prewarm_attempts', $activeHosts->count());
        Redis::hincrby('sshm:connection_stats', 'prewarm_success', $warmedCount);

        Log::info('Pre-warmed SSH connections', [
            'total_hosts' => $activeHosts->count(),
            'warmed_count' => $warmedCount,
        ]);

        return $warmedCount;
    }

    /**
     * Test connection with a lightweight command
     */
    private function testConnection(SshHost $host): bool
    {
        try {
            $connectionKey = $this->getConnectionKey($host);
            $controlPath = sys_get_temp_dir() . '/ssh-' . md5($host->hostname . $host->user);

            // Test with a very lightweight command
            $testCommand = sprintf(
                'ssh -o ConnectTimeout=2 -o BatchMode=yes -o ControlMaster=auto -o ControlPath=%s -o ControlPersist=60s %s@%s "echo test" > /dev/null 2>&1',
                escapeshellarg($controlPath),
                escapeshellarg($host->user),
                escapeshellarg($host->hostname)
            );

            $result = shell_exec($testCommand);
            $success = $result !== null;

            // Track connection test results
            Redis::hincrby('sshm:connection_stats', 'connection_tests', 1);
            if ($success) {
                Redis::hincrby('sshm:connection_stats', 'connection_tests_success', 1);
            }

            return $success;
        } catch (\Exception $e) {
            Log::debug('Connection test failed', ['host' => $host->name, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Check if host has a pre-warmed connection
     */
    public function isPreWarmed(SshHost $host): bool
    {
        $redisKey = 'sshm:prewarmed:' . $this->getConnectionKey($host);

        return Redis::exists($redisKey);
    }

    /**
     * Update connection usage statistics
     */
    private function updateConnectionUsage(string $connectionKey): void
    {
        if (isset($this->connections[$connectionKey])) {
            $this->connections[$connectionKey]['last_used'] = time();
            $this->connections[$connectionKey]['connection_count']++;
        }
    }

    /**
     * Set maximum number of concurrent connections
     */
    public function setMaxConnections(int $max): void
    {
        $this->maxConnections = max(1, $max);
    }

    /**
     * Set connection timeout in seconds
     */
    public function setConnectionTimeout(int $timeout): void
    {
        $this->connectionTimeout = max(60, $timeout);
    }
}
