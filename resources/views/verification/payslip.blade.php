<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification de bulletin de paie</title>

    <style>
        :root {
            --color-primary: {{ $company->primaryColorRgbChannels() }};
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-paper font-sans antialiased flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-md rounded-xl border border-line-soft bg-surface shadow-card p-8 text-center">
        @if ($payslip)
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-success-soft text-success">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-fg">Document authentique</h1>
            <p class="mt-1 text-sm text-muted">Ce bulletin de paie a bien été émis et validé par
                {{ $company->name }}.</p>

            <dl class="mt-6 space-y-3 text-left">
                <div class="flex justify-between border-b border-line-soft pb-2">
                    <dt class="text-sm text-muted">Employé</dt>
                    <dd class="text-sm font-medium text-fg">{{ $payslip->employee->full_name }}</dd>
                </div>
                <div class="flex justify-between border-b border-line-soft pb-2">
                    <dt class="text-sm text-muted">Période</dt>
                    <dd class="text-sm font-medium text-fg">{{ ucfirst($payslip->periodLabel()) }}</dd>
                </div>
                <div class="flex justify-between border-b border-line-soft pb-2">
                    <dt class="text-sm text-muted">Référence</dt>
                    <dd class="text-sm font-medium text-fg">{{ $payslip->reference }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-muted">Validé le</dt>
                    <dd class="text-sm font-medium text-fg">{{ $payslip->validated_at?->format('d/m/Y à H:i') }}</dd>
                </div>
            </dl>

            <p class="mt-6 text-xs text-muted">Par confidentialité, les montants ne sont pas affichés sur cette page
                de vérification publique.</p>
        @else
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-danger-soft text-danger">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-fg">Document non reconnu</h1>
            <p class="mt-1 text-sm text-muted">Aucun bulletin de paie validé ne correspond à cette référence. Ce
                document n'a pas pu être authentifié.</p>
        @endif
    </div>
</body>

</html>
