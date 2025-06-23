<?php

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
