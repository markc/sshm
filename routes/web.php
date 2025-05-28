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
}
