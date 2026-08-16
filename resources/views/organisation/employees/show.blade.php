<x-app-layout>
    <x-page-header :title="$employee->full_name">
        <x-slot:description>
            {{ $employee->employee_number }}
            @if ($employee->position)
                &middot; {{ $employee->position->title }}
            @endif
            @if ($employee->department)
                &middot; {{ $employee->department->name }}
            @endif
            @if ($employee->is_anonymized)
                <x-status-chip tone="neutral" class="ml-2">
                    Anonymisé le {{ $employee->anonymized_at?->format('d/m/Y') }}
                </x-status-chip>
            @endif
        </x-slot:description>

        <x-slot:actions>
            <a href="{{ route('organisation.employees.index') }}">
                <x-secondary-button type="button">{{ __('Retour') }}</x-secondary-button>
            </a>
            @can('employees.manage')
                @unless ($employee->is_anonymized)
                    <a href="{{ route('organisation.employees.edit', $employee) }}">
                        <x-secondary-button type="button">{{ __('Modifier') }}</x-secondary-button>
                    </a>
                @endunless
            @endcan
            @can('employees.anonymize')
                @unless ($employee->is_anonymized)
                    <form method="POST" action="{{ route('organisation.employees.anonymize', $employee) }}"
                        data-confirm="Anonymiser définitivement les données personnelles de cet employé ? Cette action est irréversible (droit à l'oubli RGPD).">
                        @csrf
                        <x-danger-button type="submit">{{ __('Anonymiser (RGPD)') }}</x-danger-button>
                    </form>
                @endunless
            @endcan
        </x-slot:actions>
    </x-page-header>

    @php
        $openedModal = old('_modal');
        $defaultTab = match (true) {
            is_string($openedModal) && str_starts_with($openedModal, 'contract') => 'contracts',
            is_string($openedModal) && str_starts_with($openedModal, 'leave') => 'leaves',
            is_string($openedModal) && str_starts_with($openedModal, 'signature') => 'signatures',
            is_string($openedModal) && str_starts_with($openedModal, 'time-entry') => 'attendance',
            is_string($openedModal) && str_starts_with($openedModal, 'pay-component') => 'payroll',
            $errors->hasAny(['title', 'category', 'file']) => 'documents',
            default => session('open_tab', 'infos'),
        };
    @endphp

    <div x-data="{ tab: '{{ $defaultTab }}' }">
        <div class="border-b border-line mb-6">
            <nav class="-mb-px flex space-x-6 overflow-x-auto">
                <button type="button" x-on:click="tab = 'infos'"
                    class="shrink-0 whitespace-nowrap py-3 text-sm font-medium border-b-2"
                    :class="tab === 'infos' ? 'border-brand text-brand' : 'border-transparent text-muted'">
                    Informations
                </button>
                <button type="button" x-on:click="tab = 'contracts'"
                    class="shrink-0 whitespace-nowrap py-3 text-sm font-medium border-b-2"
                    :class="tab === 'contracts' ? 'border-brand text-brand' : 'border-transparent text-muted'">
                    Contrats
                </button>
                <button type="button" x-on:click="tab = 'documents'"
                    class="shrink-0 whitespace-nowrap py-3 text-sm font-medium border-b-2"
                    :class="tab === 'documents' ? 'border-brand text-brand' : 'border-transparent text-muted'">
                    Documents
                </button>
                <button type="button" x-on:click="tab = 'leaves'"
                    class="shrink-0 whitespace-nowrap py-3 text-sm font-medium border-b-2"
                    :class="tab === 'leaves' ? 'border-brand text-brand' : 'border-transparent text-muted'">
                    Congés
                </button>
                <button type="button" x-on:click="tab = 'signatures'"
                    class="shrink-0 whitespace-nowrap py-3 text-sm font-medium border-b-2"
                    :class="tab === 'signatures' ? 'border-brand text-brand' : 'border-transparent text-muted'">
                    Signatures
                </button>
                <button type="button" x-on:click="tab = 'attendance'"
                    class="shrink-0 whitespace-nowrap py-3 text-sm font-medium border-b-2"
                    :class="tab === 'attendance' ? 'border-brand text-brand' : 'border-transparent text-muted'">
                    Présences
                </button>
                @can('payroll.manage')
                    <button type="button" x-on:click="tab = 'payroll'"
                        class="shrink-0 whitespace-nowrap py-3 text-sm font-medium border-b-2"
                        :class="tab === 'payroll' ? 'border-brand text-brand' : 'border-transparent text-muted'">
                        Paie
                    </button>
                @endcan
            </nav>
        </div>

        <div x-show="tab === 'infos'">
            <div
                class="rounded-xl border border-line-soft bg-surface shadow-card p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                <div>
                    <dt class="text-xs uppercase text-muted">Genre</dt>
                    <dd class="text-sm text-fg">
                        {{ ['male' => 'Masculin', 'female' => 'Féminin', 'other' => 'Autre'][$employee->gender] ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Date de naissance</dt>
                    <dd class="text-sm text-fg">{{ $employee->birth_date?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Lieu de naissance</dt>
                    <dd class="text-sm text-fg">{{ $employee->birth_place ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Nationalité</dt>
                    <dd class="text-sm text-fg">{{ $employee->nationality ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Email personnel</dt>
                    <dd class="text-sm text-fg">{{ $employee->personal_email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Téléphone</dt>
                    <dd class="text-sm text-fg">{{ $employee->personal_phone ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase text-muted">Adresse</dt>
                    <dd class="text-sm text-fg">
                        {{ collect([$employee->address, $employee->city, $employee->country])->filter()->join(', ') ?:'—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Site</dt>
                    <dd class="text-sm text-fg">{{ $employee->site?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Manager</dt>
                    <dd class="text-sm text-fg">{{ $employee->manager?->full_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Date d'embauche</dt>
                    <dd class="text-sm text-fg">{{ $employee->hire_date->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Statut</dt>
                    <dd class="text-sm text-fg">
                        {{ \App\Models\Employee::statuses()[$employee->status] ?? $employee->status }}</dd>
                </div>
            </div>

            @can('employees.manage')
                <div class="mt-6 rounded-xl border border-line-soft bg-surface shadow-card p-6">
                    <h3 class="text-base font-semibold text-fg">Accès à l'espace employé</h3>
                    @if ($employee->user_id)
                        <p class="mt-1 text-sm text-muted">
                            Compte actif : <span class="text-fg">{{ $employee->user->email }}</span>
                        </p>
                        <form method="POST" action="{{ route('organisation.employees.account.resend', $employee) }}"
                            class="mt-4">
                            @csrf
                            <x-secondary-button
                                type="submit">{{ __('Renvoyer le lien de connexion') }}</x-secondary-button>
                        </form>
                    @else
                        <p class="mt-1 text-sm text-muted">
                            Cet employé n'a pas encore d'accès à "Mon espace". Créez-en un pour qu'il puisse consulter
                            son profil, demander des congés et suivre ses documents.
                        </p>
                        <form method="POST" action="{{ route('organisation.employees.account.store', $employee) }}"
                            class="mt-4 flex flex-wrap items-end gap-3">
                            @csrf
                            <div class="w-full max-w-xs">
                                <x-input-label for="account_email" value="Email de connexion" />
                                <x-text-input name="email" id="account_email"
                                    value="{{ old('email', $employee->personal_email) }}" type="email" class="mt-1" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <x-primary-button type="submit">{{ __('Créer un accès') }}</x-primary-button>
                        </form>
                    @endif
                </div>
            @endcan
        </div>

        <div x-show="tab === 'contracts'" x-cloak>
            @include('organisation.employees.partials.contracts')
        </div>

        <div x-show="tab === 'documents'" x-cloak>
            @include('organisation.employees.partials.documents')
        </div>

        <div x-show="tab === 'leaves'" x-cloak>
            @include('organisation.employees.partials.leaves')
        </div>

        <div x-show="tab === 'signatures'" x-cloak>
            @include('organisation.employees.partials.signatures')
        </div>

        <div x-show="tab === 'attendance'" x-cloak>
            @include('organisation.employees.partials.attendance')
        </div>

        @can('payroll.manage')
            <div x-show="tab === 'payroll'" x-cloak>
                @include('organisation.employees.partials.payroll')
            </div>
        @endcan
    </div>
</x-app-layout>
