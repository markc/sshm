<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DesktopAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
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

                // Use loginUsingId with remember = true to persist the session
                Auth::loginUsingId($desktopUser->id, true);

                // Regenerate session to ensure fresh auth state
                $request->session()->regenerate();
            }
        }

        return $next($request);
    }
}
