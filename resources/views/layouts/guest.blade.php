<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIRH') }}</title>

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

<body class="h-full font-sans antialiased">
    <div class="flex min-h-full">
        <!-- Brand panel -->
        <div
            class="relative hidden overflow-hidden bg-gradient-to-b from-ink to-ink-2 lg:flex lg:w-1/2 lg:flex-col lg:px-12 lg:py-12">
            <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand/20 blur-3xl">
            </div>
            <div class="pointer-events-none absolute -left-24 bottom-0 h-96 w-96 rounded-full bg-brand/10 blur-3xl">
            </div>

            <a href="/" class="relative flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand">
                    <x-application-logo class="h-5 w-5 text-white" />
                </span>
                <span class="text-base font-semibold text-white">{{ config('app.name', 'SIRH') }}</span>
            </a>

            <div class="relative flex flex-1 flex-col justify-center gap-8 overflow-y-auto py-10">
                <div>
                    <h2 class="whitespace-nowrap text-2xl font-semibold leading-tight text-white">
                        <span class="text-brand">G</span>érez <span class="text-brand">V</span>os
                        <span class="text-brand">E</span>mployés <span class="text-brand">S</span>implement.
                    </h2>
                    <p class="mt-4 max-w-md text-sm text-slate-400">
                        {{ __('Organisation, temps de travail, congés, paie et documents RH réunis dans un seul espace sécurisé.') }}
                    </p>
                </div>

                {{-- Stylized team preview --}}
                <div class="max-w-md rounded-2xl border border-white/10 bg-surface p-5 shadow-pop">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-fg">{{ __('Votre équipe') }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ __('128 employés actifs') }}</p>
                        </div>
                        <span
                            class="rounded-md bg-brand/10 px-2 py-1 text-[11px] font-medium text-brand">{{ __('+12 ce mois-ci') }}</span>
                    </div>

                    <div class="mt-4 flex items-center">
                        @php
                            $teamPreview = [
                                ['initials' => 'AK', 'color' => '#2a78d6'],
                                ['initials' => 'FB', 'color' => '#eb6834'],
                                ['initials' => 'MK', 'color' => '#1baf7a'],
                                ['initials' => 'SD', 'color' => '#eda100'],
                                ['initials' => 'JT', 'color' => '#e87ba4'],
                            ];
                        @endphp
                        @foreach ($teamPreview as $member)
                            <span
                                class="-ml-2 flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white ring-2 ring-surface first:ml-0"
                                style="background-color: {{ $member['color'] }}">
                                {{ $member['initials'] }}
                            </span>
                        @endforeach
                        <span
                            class="-ml-2 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-2 text-xs font-semibold text-muted ring-2 ring-surface">
                            +123
                        </span>
                    </div>
                </div>

                {{-- Stylized product preview --}}
                <div class="max-w-md rounded-2xl border border-white/10 bg-surface p-5 shadow-pop">
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

                    <div class="mt-5 flex h-24 items-end gap-2 rounded-lg border border-line-soft bg-surface-2 p-4">
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
            </div>

            <p class="relative text-xs text-slate-500">
                &copy; {{ date('Y') }} {{ config('app.name', 'SIRH') }}. {{ __('Tous droits réservés.') }}
            </p>
        </div>

        <!-- Form panel -->
        <div
            class="flex flex-1 flex-col justify-center bg-paper px-6 py-12 sm:px-12 lg:w-1/2 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm">
                <a href="/" wire:navigate class="mb-10 flex items-center gap-2.5 lg:hidden">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand">
                        <x-application-logo class="h-5 w-5 text-white" />
                    </span>
                    <span class="text-base font-semibold text-fg">{{ config('app.name', 'SIRH') }}</span>
                </a>

                {{ $slot }}
            </div>
        </div>
    </div>
</body>

</html>
