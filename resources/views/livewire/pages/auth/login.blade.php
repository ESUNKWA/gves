<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ __('Connexion') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('Accédez à votre espace RH.') }}</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form wire:submit="login" class="mt-8 space-y-5">
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="form.email" id="email" class="mt-1" type="email" name="email" required
                autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-password-input wire:model="form.password" id="password" class="mt-1" name="password"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded border-line text-brand shadow-sm focus:ring-brand dark:focus:ring-brand focus:ring-offset-surface"
                    name="remember">
                <span class="ms-2 text-sm text-muted">{{ __('Se souvenir de moi') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand hover:text-brand dark:hover:text-brand"
                    href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Mot de passe oublié ?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Se connecter') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        {{ __('Vous êtes un employé ?') }}
        <a href="{{ route('login.personnel') }}" wire:navigate class="font-medium text-brand hover:text-brand">
            {{ __('Accédez à votre espace personnel') }}
        </a>
    </p>
</div>
