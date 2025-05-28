<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
