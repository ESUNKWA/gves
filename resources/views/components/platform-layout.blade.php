<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIRH') }} — Plateforme</title>

    {{-- No CompanySetting here: this layout is central, not tenant-scoped —
    a fixed brand color instead of the per-client one. --}}
    <style>
        :root {
            --color-primary: 249 144 165;
        }
    </style>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full font-sans antialiased bg-paper text-fg">
    <div class="min-h-full">
        <header class="border-b border-line bg-surface">
            <div class="mx-auto max-w-5xl px-6 py-4">
                <span class="text-base font-semibold text-fg">{{ config('app.name', 'SIRH') }} — Console plateforme</span>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-10">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-line-soft bg-surface-2 px-4 py-3 text-sm text-fg">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg border border-danger/20 bg-danger-soft px-4 py-3 text-sm text-danger">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    {{-- <x-modal>/Alpine (x-data, $dispatch) need Alpine.js, which this app
    gets bundled through Livewire's own script rather than importing it
    directly in resources/js/app.js. Every other layout gets it for free by
    rendering an actual Livewire component (e.g. <livewire:layout.navigation
    /> in layouts/app.blade.php) which triggers Livewire's auto asset
    injection; this layout has none, so it must be pulled in explicitly. --}}
    @livewireScripts
</body>

</html>
