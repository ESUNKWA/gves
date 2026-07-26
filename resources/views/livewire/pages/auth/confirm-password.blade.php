<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (
            !Auth::guard('web')->validate([
                'email' => Auth::user()->email,
                'password' => $this->password,
            ])
        ) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ __('Confirmation requise') }}</h1>
    <p class="mt-1 text-sm text-muted">
        {{ __('Ceci est une zone sécurisée de l\'application. Merci de confirmer votre mot de passe avant de continuer.') }}
    </p>

    <form wire:submit="confirmPassword" class="mt-8 space-y-5">
        <div>
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input wire:model="password" id="password" class="mt-1" type="password" name="password" required
                autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Confirmer') }}
        </x-primary-button>
    </form>
</div>
