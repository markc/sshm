<?php

use App\Http\Controllers\OptimizedSshController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Optimized SSH execution routes (bypassing queue system for performance)
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::post('/api/ssh/stream', [OptimizedSshController::class, 'streamCommand'])
        ->name('api.ssh.stream');

    Route::get('/api/ssh/cached', [OptimizedSshController::class, 'getCachedResult'])
        ->name('api.ssh.cached');
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
