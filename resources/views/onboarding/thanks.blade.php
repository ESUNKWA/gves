<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ __('Informations transmises') }} — {{ config('app.name', 'GVES') }}</title>

    <style>
        :root {
            --color-primary: {{ \App\Models\CompanySetting::current()->primaryColorRgbChannels() }};
        }
    </style>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-paper text-fg">
    <main class="mx-auto flex min-h-screen max-w-lg flex-col items-center justify-center px-6 text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-brand/10 text-brand">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </span>

        <h1 class="mt-6 text-2xl font-semibold tracking-tight text-fg">{{ __('Informations transmises') }}</h1>
        <p class="mt-3 text-sm leading-relaxed text-muted">
            {{ __('Merci ! Vos informations ont bien été transmises aux ressources humaines, qui créeront (ou mettront à jour) votre fiche employé après vérification.') }}
        </p>

        <a href="/"
            class="mt-8 text-sm font-medium text-brand hover:underline">{{ __('Retour à l\'accueil') }}</a>
    </main>
</body>

</html>
