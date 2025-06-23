<?php

use App\Http\Controllers\SshExecutionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:web');

// Debug route without authentication
Route::get('/debug', function () {
    return response()->json([
        'status' => 'API is working',
        'timestamp' => now(),
        'guards' => config('auth.guards'),
        'default_guard' => config('auth.defaults.guard'),
    ]);
});

/*
|--------------------------------------------------------------------------
| SSH Terminal API Routes
|--------------------------------------------------------------------------
|
| These routes handle real-time SSH process management including starting,
| stopping, and checking the status of SSH command executions.
|
*/

Route::middleware(['auth:web'])->group(function () {
    Route::post('/ssh/start', [SshExecutionController::class, 'start'])
        ->name('api.ssh.start');

    Route::post('/ssh/stop', [SshExecutionController::class, 'stop'])
        ->name('api.ssh.stop');

    Route::get('/ssh/status', [SshExecutionController::class, 'status'])
        ->name('api.ssh.status');
});
