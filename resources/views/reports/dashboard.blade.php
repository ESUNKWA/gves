<x-app-layout>
    <div id="reports-dashboard" x-data="{ tab: 'workforce' }">
        <x-page-header :title="__('Rapports & pilotage')" :description="__('Vue d\'ensemble RH pour l\'année :year.', ['year' => $year])">
            <x-slot:actions>
                <form method="GET" action="{{ route('reports.dashboard') }}">
                    <x-select name="year" onchange="this.form.submit()">
                        @foreach ($availableYears as $availableYear)
                            <option value="{{ $availableYear }}" @selected($year === $availableYear)>{{ $availableYear }}
                            </option>
                        @endforeach
                    </x-select>
                </form>
            </x-slot:actions>
        </x-page-header>

        <script id="reports-data" type="application/json">@json($chartData)</script>

        <div class="mb-8 border-b border-line">
            <nav class="-mb-px flex gap-6 overflow-x-auto">
                <button type="button" x-on:click="tab = 'workforce'"
                    class="shrink-0 border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                    :class="tab === 'workforce' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-fg'">
                    {{ __('Effectifs') }}
                </button>
                <button type="button" x-on:click="tab = 'attendance'; $dispatch('reports-tab-click', 'attendance')"
                    class="shrink-0 border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                    :class="tab === 'attendance' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-fg'">
                    {{ __('Présences') }}
                </button>
                <button type="button" x-on:click="tab = 'leaves'; $dispatch('reports-tab-click', 'leaves')"
                    class="shrink-0 border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                    :class="tab === 'leaves' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-fg'">
                    {{ __('Congés') }}
                </button>
                <button type="button" x-on:click="tab = 'payroll'; $dispatch('reports-tab-click', 'payroll')"
                    class="shrink-0 border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                    :class="tab === 'payroll' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-fg'">
                    {{ __('Paie') }}
                </button>
                <button type="button" x-on:click="tab = 'movements'; $dispatch('reports-tab-click', 'movements')"
                    class="shrink-0 border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                    :class="tab === 'movements' ? 'border-brand text-brand' : 'border-transparent text-muted hover:text-fg'">
                    {{ __('Mouvements') }}
                </button>
            </nav>
        </div>

        {{-- Effectifs --}}
        <div x-show="tab === 'workforce'">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-fg">{{ __('Effectifs') }}</h2>
                <a href="{{ route('reports.export.workforce') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-fg shadow-sm hover:bg-surface-2">
                    <svg class="h-4 w-4 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('Exporter CSV') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card :label="__('Employés actifs')" :value="$workforce['total']" />
                <x-stat-card :label="__('Ancienneté moyenne')" :value="$workforce['average_tenure_years'] . ' ' . __('ans')" />
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Répartition par département') }}</h3>
                    <div class="h-64"><canvas id="chart-workforce-department"></canvas></div>
                </div>
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Répartition par site') }}</h3>
                    <div class="h-64"><canvas id="chart-workforce-site"></canvas></div>
                </div>
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Pyramide des âges') }}</h3>
                    <div class="h-64"><canvas id="chart-workforce-age"></canvas></div>
                </div>
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Répartition par statut') }}</h3>
                    <div class="h-64"><canvas id="chart-workforce-status"></canvas></div>
                </div>
            </div>
        </div>

        {{-- Présences --}}
        <div x-show="tab === 'attendance'" x-cloak>
            <h2 class="mb-4 text-lg font-semibold text-fg">{{ __('Présences') }}</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card :label="__('Taux de ponctualité (mois en cours)')" :value="$attendance['punctuality_rate'] !== null ? $attendance['punctuality_rate'] . '%' : __('N/A')" />
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Retards par mois') }}</h3>
                    <div class="h-64"><canvas id="chart-attendance-late"></canvas></div>
                </div>
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-5">
                    <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Heures supplémentaires par mois') }}</h3>
                    <div class="h-64"><canvas id="chart-attendance-overtime"></canvas></div>
                </div>
            </div>
        </div>

        {{-- Congés --}}
        <div x-show="tab === 'leaves'" x-cloak>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-fg">{{ __('Congés') }}</h2>
                <a href="{{ route('reports.export.leaves', ['year' => $year]) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-fg shadow-sm hover:bg-surface-2">
                    <svg class="h-4 w-4 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('Exporter CSV') }}
                </a>
            </div>

            <div class="rounded-xl border border-line-soft bg-surface shadow-card p-5">
                <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Acquis / Pris / Restant par type de congé') }}
                </h3>
                <div class="h-80"><canvas id="chart-leaves"></canvas></div>
            </div>
        </div>

        {{-- Paie --}}
        <div x-show="tab === 'payroll'" x-cloak>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-fg">{{ __('Paie') }}</h2>
                <a href="{{ route('reports.export.payroll', ['year' => $year]) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-fg shadow-sm hover:bg-surface-2">
                    <svg class="h-4 w-4 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('Exporter CSV') }}
                </a>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card :label="__('Masse salariale brute (cumul annuel)')" :value="number_format($payroll['total_gross_ytd'], 0, ',', ' ')" />
                <x-stat-card :label="__('Masse salariale nette (cumul annuel)')" :value="number_format($payroll['total_net_ytd'], 0, ',', ' ')" />
            </div>

            <div class="mt-5 rounded-xl border border-line-soft bg-surface shadow-card p-5">
                <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Évolution mensuelle de la masse salariale') }}
                </h3>
                <div class="h-80"><canvas id="chart-payroll"></canvas></div>
            </div>
        </div>

        {{-- Mouvements --}}
        <div x-show="tab === 'movements'" x-cloak>
            <h2 class="mb-4 text-lg font-semibold text-fg">{{ __('Mouvements') }}</h2>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <x-stat-card :label="__('Entrées (cumul annuel)')" :value="$movements['total_hires']" />
                <x-stat-card :label="__('Sorties (cumul annuel)')" :value="$movements['total_departures']" />
            </div>

            <div class="mt-5 rounded-xl border border-line-soft bg-surface shadow-card p-5">
                <h3 class="mb-3 text-sm font-semibold text-fg">{{ __('Entrées / Sorties par mois') }}</h3>
                <div class="h-80"><canvas id="chart-movements"></canvas></div>
            </div>
        </div>
    </div>
</x-app-layout>
