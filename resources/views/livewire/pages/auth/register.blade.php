<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ __('Créer un compte') }}</h1>
    <p class="mt-1 text-sm text-muted">{{ __('Renseignez vos informations pour commencer.') }}</p>

    <form wire:submit="register" class="mt-8 space-y-5">
        <div>
            <x-input-label for="name" :value="__('Nom complet')" />
            <x-text-input wire:model="name" id="name" class="mt-1" type="text" name="name" required autofocus
                autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="mt-1" type="email" name="email" required
                autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input wire:model="password" id="password" class="mt-1" type="password" name="password" required
                autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1" type="password"
                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Créer mon compte') }}
        </x-primary-button>

        <p class="text-center text-sm text-muted">
            {{ __('Déjà inscrit ?') }}
            <a class="font-medium text-brand hover:text-brand dark:hover:text-brand" href="{{ route('login') }}"
                wire:navigate>
                {{ __('Se connecter') }}
            </a>
        </p>
    </form>
</div>
