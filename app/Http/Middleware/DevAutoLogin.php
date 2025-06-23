<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DevAutoLogin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only auto-login in development mode when DEV_NO_AUTH is enabled
        if (config('app.env') === 'local' && config('app.dev_no_auth', false)) {
            if (! Auth::check()) {
                // Find or create a development admin user
                $user = User::firstOrCreate(
                    ['email' => 'dev@sshm.local'],
                    [
                        'name' => 'Development Admin',
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                    ]
                );

                Auth::login($user);
            }
        }

        return $next($request);
    }
}
