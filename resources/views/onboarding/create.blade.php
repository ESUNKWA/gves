<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ __('Renseigner mes informations') }} — {{ config('app.name', 'GVES') }}</title>

    <!-- Brand color, configurable per client from Administration > Entreprise -->
    <style>
        :root {
            --color-primary: {{ \App\Models\CompanySetting::current()->primaryColorRgbChannels() }};
        }
    </style>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-paper text-fg">
    <header class="border-b border-line bg-surface/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-6">
            <a href="/" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand">
                    <x-application-logo class="h-5 w-5 text-white" />
                </span>
                <span class="text-base font-semibold tracking-tight text-fg">{{ config('app.name', 'GVES') }}</span>
            </a>

            <a href="{{ route('login') }}" class="text-sm font-medium text-muted transition hover:text-fg">
                {{ __('Se connecter') }}
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-12">
        @if (!$isOpen)
            <div class="rounded-xl border border-line-soft bg-surface p-8 text-center shadow-card">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-surface-2 text-muted">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <h1 class="mt-4 text-xl font-semibold tracking-tight text-fg">
                    {{ __('Ce lien n\'est pas disponible pour le moment') }}</h1>
                <p class="mt-2 text-sm text-muted">
                    {{ __('La saisie en ligne n\'est pas ouverte actuellement. Contactez les ressources humaines pour plus d\'informations.') }}
                </p>
            </div>
        @else
            <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ __('Renseignez vos informations') }}</h1>
            <p class="mt-2 text-sm text-muted">
                {{ __('Ces informations seront transmises aux ressources humaines pour la création (ou la mise à jour) de votre fiche employé — pas besoin de repasser par eux pour la saisie.') }}
            </p>

            <form method="POST" action="{{ route('onboarding.store') }}"
                class="mt-8 space-y-8 rounded-xl border border-line-soft bg-surface p-6 shadow-card">
                @csrf

                <div>
                    <h3 class="text-base font-semibold text-fg">{{ __('Identité') }}</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="first_name" :value="__('Prénom')" />
                            <x-text-input name="first_name" id="first_name" value="{{ old('first_name') }}"
                                class="mt-1" required autofocus />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="last_name" :value="__('Nom')" />
                            <x-text-input name="last_name" id="last_name" value="{{ old('last_name') }}" class="mt-1"
                                required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gender" :value="__('Genre')" />
                            <x-select name="gender" id="gender" class="mt-1">
                                <option value="">—</option>
                                <option value="male" @selected(old('gender') === 'male')>{{ __('Masculin') }}</option>
                                <option value="female" @selected(old('gender') === 'female')>{{ __('Féminin') }}</option>
                                <option value="other" @selected(old('gender') === 'other')>{{ __('Autre') }}</option>
                            </x-select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="birth_date" :value="__('Date de naissance')" />
                            <x-text-input name="birth_date" id="birth_date" value="{{ old('birth_date') }}"
                                type="date" class="mt-1" />
                            <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="national_id" :value="__('N° pièce d\'identité')" />
                            <x-text-input name="national_id" id="national_id" value="{{ old('national_id') }}"
                                class="mt-1" />
                            <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="marital_status" :value="__('Situation familiale')" />
                            <x-text-input name="marital_status" id="marital_status" value="{{ old('marital_status') }}"
                                class="mt-1" />
                            <x-input-error :messages="$errors->get('marital_status')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-fg">{{ __('Coordonnées') }}</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="personal_email" :value="__('Email')" />
                            <x-text-input name="personal_email" id="personal_email" value="{{ old('personal_email') }}"
                                type="email" class="mt-1" required />
                            <x-input-error :messages="$errors->get('personal_email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="personal_phone" :value="__('Téléphone')" />
                            <x-text-input name="personal_phone" id="personal_phone" value="{{ old('personal_phone') }}"
                                class="mt-1" />
                            <x-input-error :messages="$errors->get('personal_phone')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="address" :value="__('Adresse')" />
                            <x-text-input name="address" id="address" value="{{ old('address') }}"
                                class="mt-1" />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="city" :value="__('Ville')" />
                            <x-text-input name="city" id="city" value="{{ old('city') }}"
                                class="mt-1" />
                            <x-input-error :messages="$errors->get('city')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="country" :value="__('Pays')" />
                            <x-country-select name="country" id="country" :selected="old('country')" class="mt-1" />
                            <x-input-error :messages="$errors->get('country')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-line-soft pt-6">
                    <x-primary-button type="submit">
                        {{ __('Envoyer mes informations') }}
                    </x-primary-button>
                </div>
            </form>
        @endif
    </main>
</body>

</html>
