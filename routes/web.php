<?php

use App\Http\Controllers\OptimizedSshController;
use App\Http\Controllers\XtermWebSocketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Optimized SSH execution routes (bypassing queue system for performance)
$middlewareGroup = ['throttle:60,1'];

// Add appropriate auth middleware based on desktop mode
if (config('app.desktop_mode', false)) {
    $middlewareGroup[] = \App\Http\Middleware\DesktopAuthenticate::class;
} else {
    $middlewareGroup[] = 'auth';
}

Route::middleware($middlewareGroup)->group(function () {
    // Existing SSE-based SSH routes
    Route::post('/api/ssh/stream', [OptimizedSshController::class, 'streamCommand'])
        ->name('api.ssh.stream');

    Route::get('/api/ssh/cached', [OptimizedSshController::class, 'getCachedResult'])
        ->name('api.ssh.cached');

    Route::get('/api/ssh/hosts', [OptimizedSshController::class, 'getHosts'])
        ->name('api.ssh.hosts');

    // Ultra-Fast Xterm.js WebSocket SSH Terminal routes
    Route::post('/api/xterm/init', [XtermWebSocketController::class, 'initializeSession'])
        ->name('api.xterm.init');

    Route::post('/api/xterm/execute', [XtermWebSocketController::class, 'executeCommand'])
        ->name('api.xterm.execute');

    Route::post('/api/xterm/input', [XtermWebSocketController::class, 'sendInput'])
        ->name('api.xterm.input');

    Route::post('/api/xterm/close', [XtermWebSocketController::class, 'closeSession'])
        ->name('api.xterm.close');

    Route::get('/api/xterm/status', [XtermWebSocketController::class, 'getSessionStatus'])
        ->name('api.xterm.status');
});

// In desktop mode, redirect any login attempts to the admin dashboard
if (config('app.desktop_mode', false)) {
    Route::get('/admin/login', function () {
        return redirect('/admin');
    })->name('filament.admin.auth.login');

    // Disable logout in desktop mode - redirect back to admin
    Route::post('/admin/logout', function () {
        return redirect('/admin')->with('status', 'Logout disabled in desktop mode');
    })->name('filament.admin.auth.logout');
}
