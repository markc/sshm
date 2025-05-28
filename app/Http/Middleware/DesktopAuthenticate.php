<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesktopAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if desktop mode is enabled
        if (config('app.desktop_mode', false)) {
            // Auto-login the desktop user
            if (! Auth::check()) {
                $desktopUser = User::firstOrCreate(
                    ['email' => config('app.desktop_user_email', 'desktop@localhost')],
                    [
                        'name' => config('app.desktop_user_name', 'Desktop User'),
                        'password' => bcrypt(uniqid('desktop_', true)),
                    ]
                );

                Auth::login($desktopUser);
            }
        }

        return $next($request);
    }
}
