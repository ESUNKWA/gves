<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ __('Vérifiez votre email') }}</h1>
    <p class="mt-1 text-sm text-muted">
        {{ __('Merci de votre inscription ! Avant de commencer, pouvez-vous vérifier votre adresse email en cliquant sur le lien que nous venons de vous envoyer ? Si vous ne l\'avez pas reçu, nous pouvons vous en renvoyer un.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-6 rounded-lg bg-success-soft p-3 text-sm font-medium text-success">
            {{ __('Un nouveau lien de vérification a été envoyé à l\'adresse email fournie lors de votre inscription.') }}
        </div>
    @endif

    <div class="mt-8 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            {{ __('Renvoyer le lien de vérification') }}
        </x-primary-button>

        <button wire:click="logout" type="submit" class="text-sm font-medium text-muted hover:text-fg">
            {{ __('Se déconnecter') }}
        </button>
    </div>
</div>
