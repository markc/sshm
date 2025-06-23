<?php

/**
 * FrankenPHP Worker Script for SSHM with Redis Optimization
 * Properly bootstrapped with database and Redis initialization
 */

// Include autoloader and bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Pre-warm frequently used services for better performance
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Ensure Laravel is fully booted before accessing services
$app->boot();

// Initialize Redis connection if available
$redisAvailable = false;
try {
    if (extension_loaded('redis') || class_exists('Predis\Client')) {
        $app->make('redis')->ping();
        $redisAvailable = true;
        error_log('FrankenPHP Worker: Redis connection established');
    }
} catch (Exception $e) {
    error_log('FrankenPHP Worker: Redis not available - ' . $e->getMessage());
}

// Initialize SSH connection pool service after Laravel is fully booted
$connectionPool = null;
try {
    $connectionPool = $app->make(\App\Services\SshConnectionPoolService::class);
    error_log('FrankenPHP Worker: SSH connection pool service initialized');

    // Pre-warm connections if we have active hosts (only after full bootstrap)
    if ($redisAvailable) {
        $warmedCount = $connectionPool->preWarmConnections();
        error_log("FrankenPHP Worker: Pre-warmed {$warmedCount} SSH connections");
    }
} catch (Exception $e) {
    error_log('FrankenPHP Worker: Connection pool initialization failed - ' . $e->getMessage());
}

// Handle FrankenPHP requests
while (frankenphp_handle_request(function () use ($kernel) {
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $response->send();

    $kernel->terminate($request, $response);
})) {
    // Optimized cleanup between requests
    $app->forgetInstance('auth');
    $app->forgetInstance('session');

    // Clear resolved instances to prevent memory bloat
    $app->forgetInstance('request');
    $app->forgetInstance('url');

    // Force garbage collection every 10 requests for optimal memory usage
    static $requestCount = 0;
    if (++$requestCount % 10 === 0) {
        if (gc_collect_cycles()) {
            gc_mem_caches();
        }
    }
}
