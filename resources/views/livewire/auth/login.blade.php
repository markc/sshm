<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();
        if (
            !Auth::attempt(
                ['email' => $this->email, 'password' => $this->password],
                $this->remember,
            )
        ) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }
        RateLimiter::clear($this->throttleKey());
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
    /** * Ensure the authentication request is
     not rate limited. */ protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }
        event(new Lockout(request()));
        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
    /** * Get the authentication rate limiting throttle key. */ protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
};
?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Log in to your account')"
        :description="__('Enter your email and password below to log in')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
            <input
                id="email"
                wire:model="email"
                type="email"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />
        </div>

        <!-- Password -->
        <div class="relative">
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                <input
                    id="password"
                    wire:model="password"
                    type="password"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                    required
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                />
            </div>

            @if (Route::has('password.request'))
            <a 
                class="absolute end-0 top-0 text-sm text-primary-600 hover:text-primary-700"
                href="{{ route('password.request') }}"
                wire:navigate
            >
                {{ __('Forgot your password?') }}
            </a>
            @endif
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <input id="remember_me" type="checkbox" wire:model="remember" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600">
            <label for="remember_me" class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</label>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                {{ __('Log in') }}
            </button>
        </div>
    </form>

    @if (Route::has('register'))
    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Don\'t have an account?') }}
        <a href="{{ route('register') }}" wire:navigate class="text-primary-600 hover:text-primary-700">{{ __('Sign up') }}</a>
    </div>
    @endif
</div>
