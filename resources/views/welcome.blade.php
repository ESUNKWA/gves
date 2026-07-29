<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="{{ __(':name — Gérez Vos Employés Simplement.', ['name' => config('app.name', 'SIRH')]) }}">

    <title>{{ config('app.name', 'SIRH') }} — {{ __('Gérez Vos Employés Simplement') }}</title>

    <!-- Brand color, configurable per client from Administration > Entreprise -->
    <style>
        :root {
            --color-primary: {{ \App\Models\CompanySetting::current()->primaryColorRgbChannels() }};
        }
    </style>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-paper text-fg">

    {{-- Header --}}
    <header class="sticky top-0 z-40 border-b border-line bg-surface/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 lg:px-8">
            <a href="/" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand">
                    <x-application-logo class="h-5 w-5 text-white" />
                </span>
                <span class="text-base font-semibold tracking-tight text-fg">{{ config('app.name', 'GVES') }}</span>
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-muted md:flex">
                <a href="#modules" class="transition hover:text-fg">{{ __('Modules') }}</a>
                <a href="#pourquoi" class="transition hover:text-fg">{{ __('Pourquoi GVES?') }}</a>
                <a href="#espace" class="transition hover:text-fg">{{ __('Mon espace') }}</a>
            </nav>

            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110">
                        {{ __('Tableau de bord') }}
                    </a>
                @else
                    @if (Route::has('login.personnel'))
                        <a href="{{ route('login.personnel') }}"
                            class="hidden text-sm font-medium text-muted transition hover:text-fg sm:inline">
                            {{ __('Espace personnel') }}
                        </a>
                    @endif

                    @if (Route::has('login'))
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-110">
                            {{ __('Se connecter') }}
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-ink to-ink-2">
        <div
            class="pointer-events-none absolute -right-32 -top-32 h-[28rem] w-[28rem] rounded-full bg-brand/25 blur-3xl">
        </div>
        <div
            class="pointer-events-none absolute -left-32 top-1/2 h-[28rem] w-[28rem] -translate-y-1/2 rounded-full bg-brand/10 blur-3xl">
        </div>

        <div class="relative mx-auto grid max-w-7xl gap-16 px-6 py-20 lg:grid-cols-2 lg:items-center lg:py-28 lg:px-8">
            <div>
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                    {{ __('8 modules RH réunis dans un seul espace') }}
                </span>

                <h1 class="mt-6 text-4xl font-semibold leading-[1.1] tracking-tight text-white sm:text-5xl">
                    {{ __('Gérez Vos Employés') }}<br class="hidden sm:block">
                    {{ __('Simplement.') }}
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-400">
                    {{ __('Organisation, temps de travail, congés, paie, documents et pilotage RH réunis dans un seul espace sécurisé — déployé pour votre entreprise, avec votre devise et votre législation.') }}
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white shadow-pop transition hover:brightness-110">
                            {{ __('Accéder à mon tableau de bord') }}
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white shadow-pop transition hover:brightness-110">
                                {{ __('Se connecter à mon espace') }}
                            </a>
                        @endif
                    @endauth

                    <a href="#modules"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/15 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/5">
                        {{ __('Découvrir les modules') }}
                    </a>
                </div>

                <dl class="mt-14 grid max-w-md grid-cols-3 gap-6 border-t border-white/10 pt-8">
                    <div>
                        <dt class="text-2xl font-semibold text-white">8</dt>
                        <dd class="mt-1 text-xs text-slate-400">{{ __('Modules RH intégrés') }}</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-semibold text-white">100%</dt>
                        <dd class="mt-1 text-xs text-slate-400">{{ __('Paie paramétrable') }}</dd>
                    </div>
                    <div>
                        <dt class="text-2xl font-semibold text-white">1</dt>
                        <dd class="mt-1 text-xs text-slate-400">{{ __('Instance dédiée à votre entreprise') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Stylized product preview --}}
            <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                <div class="rounded-2xl border border-white/10 bg-surface p-5 shadow-pop">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-fg">{{ __('Rapports & pilotage') }}</p>
                        <span
                            class="rounded-md bg-brand/10 px-2 py-1 text-[11px] font-medium text-brand">{{ now()->format('Y') }}</span>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-line-soft bg-surface-2 p-3">
                            <p class="text-[11px] text-muted">{{ __('Effectifs') }}</p>
                            <p class="mt-1 text-lg font-semibold text-fg">128</p>
                        </div>
                        <div class="rounded-lg border border-line-soft bg-surface-2 p-3">
                            <p class="text-[11px] text-muted">{{ __('Ponctualité') }}</p>
                            <p class="mt-1 text-lg font-semibold text-fg">96%</p>
                        </div>
                        <div class="rounded-lg border border-line-soft bg-surface-2 p-3">
                            <p class="text-[11px] text-muted">{{ __('Masse salariale') }}</p>
                            <p class="mt-1 text-lg font-semibold text-fg">42,5M</p>
                        </div>
                    </div>

                    <div class="mt-5 flex h-28 items-end gap-2 rounded-lg border border-line-soft bg-surface-2 p-4">
                        @foreach ([45, 60, 38, 70, 55, 80, 65, 90, 72, 85, 60, 95] as $bar)
                            <span class="flex-1 rounded-t-sm"
                                style="height: {{ $bar }}%; background-color: rgb(var(--color-primary) / {{ $loop->last ? '1' : '0.35' }})"></span>
                        @endforeach
                    </div>

                    <div
                        class="mt-5 flex items-center justify-between rounded-lg border border-line-soft bg-surface-2 px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand/10 text-brand">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <p class="text-sm text-fg">{{ __('Bulletin de paie de juillet validé') }}</p>
                        </div>
                        <span class="text-xs text-muted">{{ __('à l\'instant') }}</span>
                    </div>
                </div>

                <div
                    class="absolute -bottom-6 -right-6 hidden rounded-xl border border-line-soft bg-surface p-4 shadow-pop lg:block">
                    <p class="text-xs text-muted">{{ __('Congés en attente') }}</p>
                    <p class="mt-1 text-xl font-semibold text-fg">3</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Modules --}}
    <section id="modules" class="mx-auto max-w-7xl scroll-mt-20 px-6 py-24 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-brand">{{ __('Modules') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-fg sm:text-4xl">
                {{ __('Un module pour chaque étape du parcours RH.') }}</h2>
            <p class="mt-4 text-base leading-relaxed text-muted">
                {{ __("De l'organigramme au bulletin de paie, chaque module s'intègre nativement aux autres — les mêmes employés, les mêmes permissions, un seul espace.") }}
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $modules = [
                    [
                        'title' => __('Organisation & employés'),
                        'description' => __(
                            'Sites, départements, postes et fiches employés complètes, avec organigramme manager/équipe.',
                        ),
                        'path' =>
                            'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                    ],
                    [
                        'title' => __('Temps & présences'),
                        'description' => __(
                            'Pointage en libre-service, horaires hebdomadaires par employé, suivi des retards et heures supplémentaires.',
                        ),
                        'path' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    ],
                    [
                        'title' => __('Congés & absences'),
                        'description' => __(
                            'Types de congés paramétrables, calcul automatique des soldes et validation par le manager.',
                        ),
                        'path' =>
                            'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
                    ],
                    [
                        'title' => __('Paie'),
                        'description' => __(
                            'Moteur de paie 100% paramétrable, bulletins PDF et vérification par QR code, charges patronales incluses.',
                        ),
                        'path' =>
                            'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z',
                    ],
                    [
                        'title' => __('Documents & signatures'),
                        'description' => __(
                            'Gabarits d\'attestations, demandes en libre-service et signature électronique native.',
                        ),
                        'path' =>
                            'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM19.5 13.5v5.25a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V8.25a2.25 2.25 0 0 1 2.25-2.25h5.25',
                    ],
                    [
                        'title' => __('Rapports & pilotage'),
                        'description' => __(
                            'Tableaux de bord et graphiques sur effectifs, présences, congés, paie et mouvements.',
                        ),
                        'path' =>
                            'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
                    ],
                    [
                        'title' => __('Administration'),
                        'description' => __('Rôles & permissions, jours fériés, pays et paramètres de l\'entreprise.'),
                        'path' =>
                            'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a7.48 7.48 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                    ],
                    [
                        'title' => __('Mon espace'),
                        'description' => __(
                            'Portail self-service par employé : profil, pointage, congés, documents et bulletins de paie.',
                        ),
                        'path' =>
                            'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                    ],
                ];
            @endphp

            @foreach ($modules as $module)
                <div
                    class="group rounded-xl border border-line-soft bg-surface p-6 shadow-card transition hover:-translate-y-0.5 hover:shadow-pop">
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $module['path'] }}" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-sm font-semibold text-fg">{{ $module['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $module['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Why SIRH --}}
    <section id="pourquoi" class="scroll-mt-20 border-y border-line bg-surface">
        <div class="mx-auto max-w-7xl px-6 py-24 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand">{{ __('Pourquoi GVES') }}</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-fg sm:text-4xl">
                    {{ __('Une plateforme pensée pour s\'adapter à votre entreprise, pas l\'inverse.') }}</h2>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-10 lg:grid-cols-3">
                <div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H5.25a2.25 2.25 0 0 1-2.25-2.25V6.75a2.25 2.25 0 0 1 2.25-2.25h5.379a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18.75a2.25 2.25 0 0 1 2.25 2.25v6.75a2.25 2.25 0 0 1-2.25 2.25Z" />
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-semibold text-fg">{{ __('Paie sur mesure') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">
                        {{ __('Chaque rubrique de paie — gain, retenue ou charge patronale — se configure par montant fixe, pourcentage ou plafond, pour coller à votre convention et votre législation.') }}
                    </p>
                </div>

                <div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-semibold text-fg">{{ __('Rôles & permissions stricts') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">
                        {{ __('Quatre rôles — employé, manager, RH, super-admin — et une anonymisation RGPD en un clic pour les départs, sans jamais perdre l\'historique de paie.') }}
                    </p>
                </div>

                <div>
                    <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand/10 text-brand">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </span>
                    <h3 class="mt-5 text-base font-semibold text-fg">{{ __('Multi-sites, une seule vue') }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">
                        {{ __('Sites, départements et postes s\'organisent librement, avec un pilotage consolidé sur l\'ensemble de vos implantations.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Mon espace --}}
    <section id="espace" class="mx-auto max-w-7xl scroll-mt-20 px-6 py-24 lg:px-8">
        <div class="grid grid-cols-1 gap-16 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand">{{ __('Mon espace') }}</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-fg sm:text-4xl">
                    {{ __('Chaque employé gère son quotidien, sans solliciter les RH.') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-muted">
                    {{ __('Le portail self-service met entre les mains de chaque collaborateur ce qui le concerne directement — la charge administrative des RH baisse d\'autant.') }}
                </p>

                <ul class="mt-8 space-y-4">
                    @foreach ([__('Pointer son arrivée et son départ en un geste'), __('Poser une demande de congé et suivre son solde en temps réel'), __('Demander une attestation et signer un document électroniquement'), __('Télécharger ses bulletins de paie validés')] as $item)
                        <li class="flex items-start gap-3">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand/10 text-brand">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </span>
                            <span class="text-sm text-fg">{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-2xl border border-line-soft bg-surface p-6 shadow-card">
                <p class="text-sm font-semibold text-fg">{{ __('Mes congés') }}</p>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg border border-line-soft px-4 py-3">
                        <span class="text-sm text-fg">{{ __('Congé payé') }}</span>
                        <span class="text-sm font-medium text-muted">18,5 {{ __('j. restants') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-line-soft px-4 py-3">
                        <span class="text-sm text-fg">{{ __('Congé maladie') }}</span>
                        <span class="text-sm font-medium text-muted">3 {{ __('j. pris') }}</span>
                    </div>
                </div>

                <p class="mt-6 text-sm font-semibold text-fg">{{ __('Mon pointage — aujourd\'hui') }}</p>
                <div class="mt-4 flex items-center justify-between rounded-lg border border-line-soft px-4 py-3">
                    <div>
                        <p class="text-xs text-muted">{{ __('Arrivée') }}</p>
                        <p class="text-sm font-medium text-fg">08:02</p>
                    </div>
                    <div class="h-8 w-px bg-line"></div>
                    <div>
                        <p class="text-xs text-muted">{{ __('Départ') }}</p>
                        <p class="text-sm font-medium text-muted">—</p>
                    </div>
                    <span
                        class="rounded-md bg-brand/10 px-2.5 py-1 text-xs font-medium text-brand">{{ __('En poste') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA banner --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-ink to-ink-2">
        <div
            class="pointer-events-none absolute left-1/2 top-0 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand/20 blur-3xl">
        </div>
        <div class="relative mx-auto max-w-3xl px-6 py-24 text-center lg:px-8">
            <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                {{ __('Prêt à simplifier la gestion RH de votre entreprise ?') }}</h2>
            <p class="mt-4 text-base text-slate-400">
                {{ __('Connectez-vous à votre espace pour retrouver vos employés, votre paie et vos rapports.') }}</p>

            <div class="mt-8">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white shadow-pop transition hover:brightness-110">
                        {{ __('Accéder à mon tableau de bord') }}
                    </a>
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-6 py-3 text-sm font-semibold text-white shadow-pop transition hover:brightness-110">
                            {{ __('Se connecter') }}
                        </a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-line bg-paper">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-10 sm:flex-row lg:px-8">
            <a href="/" class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand">
                    <x-application-logo class="h-4 w-4 text-white" />
                </span>
                <span class="text-sm font-semibold text-fg">{{ config('app.name', 'SIRH') }}</span>
            </a>

            <p class="text-xs text-muted">
                &copy; {{ date('Y') }} {{ config('app.name', 'SIRH') }}. {{ __('Tous droits réservés.') }}
            </p>
        </div>
    </footer>

</body>

</html>
