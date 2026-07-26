<x-app-layout>
    <x-page-header title="{{ __('Tableau de bord') }}" :description="__('Bonjour :name, voici un aperçu de votre espace.', [
        'name' => explode(' ', auth()->user()->name)[0],
    ])" />

    @if ($myOverview)
        <div class="mb-8">
            <h2 class="mb-4 text-base font-semibold text-fg">{{ __('Mon espace') }}</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Pointage --}}
                <a href="{{ route('portal.time-clock.index') }}"
                    class="rounded-xl border border-line-soft bg-surface p-5 shadow-card transition hover:border-brand/40 hover:shadow-pop">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <p class="text-sm text-muted">{{ __('Mon pointage') }}</p>
                    </div>

                    <div class="mt-4">
                        @if ($myOverview['todayEntry']?->clock_in && $myOverview['todayEntry']?->clock_out)
                            <p class="text-lg font-semibold text-fg">
                                {{ $myOverview['todayEntry']->clock_in->format('H:i') }} —
                                {{ $myOverview['todayEntry']->clock_out->format('H:i') }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ __('Journée terminée') }}</p>
                        @elseif ($myOverview['todayEntry']?->clock_in)
                            <p class="text-lg font-semibold text-fg">
                                {{ $myOverview['todayEntry']->clock_in->format('H:i') }}</p>
                            <p class="mt-0.5 text-xs text-success">{{ __('En poste') }}</p>
                        @else
                            <p class="text-lg font-semibold text-fg">{{ __('Pas encore pointé') }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ __("Pointer aujourd'hui") }}</p>
                        @endif
                    </div>
                </a>

                {{-- Congés --}}
                <a href="{{ route('portal.leaves.index') }}"
                    class="rounded-xl border border-line-soft bg-surface p-5 shadow-card transition hover:border-brand/40 hover:shadow-pop">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                        </span>
                        <p class="text-sm text-muted">{{ __('Mes congés') }}</p>
                    </div>

                    <div class="mt-4">
                        @if ($myOverview['leaveBalances']->isNotEmpty())
                            <p class="text-lg font-semibold text-fg">
                                {{ number_format($myOverview['leaveBalances']->first()->availableDays(), 1) }}
                                {{ __('j.') }}</p>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ $myOverview['leaveBalances']->first()->leaveType->name }}</p>
                        @else
                            <p class="text-lg font-semibold text-fg">—</p>
                        @endif

                        @if ($myOverview['pendingLeaveRequests'] > 0)
                            <p class="mt-2 text-xs text-warning">
                                {{ trans_choice('1 demande en attente|:count demandes en attente', $myOverview['pendingLeaveRequests'], ['count' => $myOverview['pendingLeaveRequests']]) }}
                            </p>
                        @endif
                    </div>
                </a>

                {{-- Documents --}}
                <a href="{{ route('portal.documents.index') }}"
                    class="rounded-xl border border-line-soft bg-surface p-5 shadow-card transition hover:border-brand/40 hover:shadow-pop">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </span>
                        <p class="text-sm text-muted">{{ __('Mes documents') }}</p>
                    </div>

                    <div class="mt-4">
                        @if ($myOverview['pendingSignatures'] > 0)
                            <p class="text-lg font-semibold text-fg">{{ $myOverview['pendingSignatures'] }}</p>
                            <p class="mt-0.5 text-xs text-warning">
                                {{ trans_choice('document à signer|documents à signer', $myOverview['pendingSignatures']) }}
                            </p>
                        @else
                            <p class="text-lg font-semibold text-fg">{{ __('À jour') }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ __('Aucune signature en attente') }}</p>
                        @endif
                    </div>
                </a>

                {{-- Paie --}}
                <a href="{{ route('portal.payslips.index') }}"
                    class="rounded-xl border border-line-soft bg-surface p-5 shadow-card transition hover:border-brand/40 hover:shadow-pop">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />
                            </svg>
                        </span>
                        <p class="text-sm text-muted">{{ __('Ma paie') }}</p>
                    </div>

                    <div class="mt-4">
                        @if ($myOverview['latestPayslip'])
                            <p class="text-lg font-semibold text-fg">
                                {{ number_format($myOverview['latestPayslip']->net_amount, 0, ',', ' ') }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ __('Net —') }}
                                {{ $myOverview['latestPayslip']->periodLabel() }}</p>
                        @else
                            <p class="text-lg font-semibold text-fg">{{ __('Aucun bulletin') }}</p>
                            <p class="mt-0.5 text-xs text-muted">{{ __('Rien de disponible pour le moment') }}</p>
                        @endif
                    </div>
                </a>
            </div>
        </div>
    @endif

    @if ($stats)
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card :label="__('Employés actifs')" :value="$stats['activeEmployees']">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('Employés au total')" :value="$stats['employees']">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('Sites')" :value="$stats['sites']">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card :label="__('Départements')" :value="$stats['departments']">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </div>

        <div class="mt-8 rounded-xl border border-line-soft bg-surface shadow-card p-6">
            <h2 class="text-base font-semibold text-fg">{{ __('Accès rapides') }}</h2>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <a href="{{ route('organisation.employees.index') }}"
                    class="flex items-center justify-between rounded-lg border border-line px-4 py-3 text-sm font-medium text-fg hover:border-brand/40 hover:bg-brand/5 dark:hover:border-brand/50 dark:hover:bg-brand/10">
                    {{ __('Gérer les employés') }}
                    <svg class="h-4 w-4 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>

                @can('employees.manage')
                    <a href="{{ route('organisation.employees.create') }}"
                        class="flex items-center justify-between rounded-lg border border-line px-4 py-3 text-sm font-medium text-fg hover:border-brand/40 hover:bg-brand/5 dark:hover:border-brand/50 dark:hover:bg-brand/10">
                        {{ __('Ajouter un employé') }}
                        <svg class="h-4 w-4 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endcan

                @can('organisation.view')
                    <a href="{{ route('organisation.sites.index') }}"
                        class="flex items-center justify-between rounded-lg border border-line px-4 py-3 text-sm font-medium text-fg hover:border-brand/40 hover:bg-brand/5 dark:hover:border-brand/50 dark:hover:bg-brand/10">
                        {{ __('Gérer les sites') }}
                        <svg class="h-4 w-4 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endcan
            </div>
        </div>
    @elseif (!$myOverview)
        <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
            <p class="text-sm text-muted">{{ __('Bienvenue sur votre espace RH.') }}</p>
        </div>
    @endif
</x-app-layout>
