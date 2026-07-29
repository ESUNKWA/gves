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

<body class="h-full font-sans antialiased bg-paper" data-flash-status="{{ session('status') }}"
    data-flash-error="{{ session('error') }}">
    <div x-data="{ sidebarOpen: false }" class="flex h-full">
        <!-- Mobile sidebar overlay -->
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-ink/60 lg:hidden"
            @click="sidebarOpen = false" style="display: none;"></div>

        <livewire:layout.navigation />

        <div class="flex flex-1 flex-col min-w-0">
            <!-- Mobile top bar -->
            <header
                class="sticky top-0 z-30 flex items-center gap-3 h-14 px-4 bg-surface/90 backdrop-blur border-b border-line lg:hidden">
                <button type="button" @click="sidebarOpen = true"
                    class="-ml-1 p-2 rounded-md text-muted hover:bg-surface-2">
                    <span class="sr-only">{{ __('Ouvrir le menu') }}</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>

                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                    <x-application-logo class="h-6 w-6 text-brand" />
                    <span class="font-semibold text-fg">{{ config('app.name', 'SIRH') }}</span>
                </a>
            </header>

            <main class="flex-1 overflow-y-auto">
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-10">
                    @isset($header)
                        <div class="mb-8">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>

</html>
