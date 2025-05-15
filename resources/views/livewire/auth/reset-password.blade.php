<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token; 
        $this->email = request()->string('email'); 
    } 
    
    /**
     * Reset the password for the given user.
     */ 
    public function resetPassword(): void 
    { 
        $this->validate([
            'token' => ['required'], 
            'email' => ['required', 'string', 'email'], 
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]); 
        
        // Here we will attempt to reset the user's password. If it is successful we 
        // will update the password on an actual user model and persist it to the 
        // database. Otherwise we will parse the error and return the response. 
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'), 
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password), 
                    'remember_token' => Str::random(60),
                ])->save(); 
                
                event(new PasswordReset($user)); 
            }
        ); 
        
        // If the password was successfully reset, we will redirect the user back to 
        // the application's home authenticated view. If there is an error we can 
        // redirect them back to where they came from with their error message. 
        if ($status != Password::PASSWORD_RESET) { 
            $this->addError('email', __($status)); 
            return; 
        } 
        
        Session::flash('status', __($status)); 
        $this->redirectRoute('login', navigate: true); 
    } 
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Reset password')"
        :description="__('Please enter your new password below')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-6">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
            <input
                id="email"
                wire:model="email"
                type="email"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                required
                autocomplete="email"
            />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
            <input
                id="password"
                wire:model="password"
                type="password"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                required
                autocomplete="new-password"
                placeholder="{{ __('Password') }}"
            />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm password') }}</label>
            <input
                id="password_confirmation"
                wire:model="password_confirmation"
                type="password"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm password') }}"
            />
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                {{ __('Reset password') }}
            </button>
        </div>
    </form>
</div>
