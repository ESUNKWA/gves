<x-app-layout>
    <x-page-header :title="__('Fiches à valider')" :description="__('Informations transmises par le personnel en auto-saisie, en attente de validation.')" />

    <div class="mb-6 rounded-xl border border-line-soft bg-surface p-4 shadow-card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-fg">{{ __('Lien à partager avec le personnel') }}</p>
                    @php
                        $onboardingTones = [
                            'Actif' => 'success',
                            'Programmé' => 'warning',
                            'Expiré' => 'warning',
                            'Désactivé' => 'neutral',
                        ];
                        $onboardingStatusLabel = $companySetting->onboardingStatusLabel();
                    @endphp
                    <x-status-chip :tone="$onboardingTones[$onboardingStatusLabel] ?? 'neutral'">
                        {{ $onboardingStatusLabel }}
                    </x-status-chip>
                </div>
                <p class="mt-0.5 truncate text-sm text-muted" id="onboarding-link">{{ url('/rejoindre') }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" x-data="{ copied: false }"
                    x-on:click="navigator.clipboard.writeText(document.getElementById('onboarding-link').innerText); copied = true; setTimeout(() => copied = false, 1500)"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-fg shadow-sm hover:bg-surface-2">
                    <span x-show="!copied">{{ __('Copier le lien') }}</span>
                    <span x-show="copied" x-cloak>{{ __('Copié !') }}</span>
                </button>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'onboarding-settings')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-fg shadow-sm hover:bg-surface-2">
                    {{ __('Paramètres') }}
                </button>
            </div>
        </div>
    </div>

    <x-modal name="onboarding-settings" focusable>
        <form method="POST" action="{{ route('organisation.employees.onboarding-settings.update') }}" class="p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-medium text-fg">{{ __('Disponibilité du lien') }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ __('Contrôlez si le lien public /rejoindre est ouvert à la saisie, et sur quelle période.') }}
            </p>

            <label class="mt-4 flex items-center gap-2">
                <input type="checkbox" name="onboarding_enabled" value="1" @checked($companySetting->onboarding_enabled)
                    class="rounded border-line text-brand shadow-sm focus:ring-brand">
                <span class="text-sm text-fg">{{ __('Lien actif') }}</span>
            </label>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="onboarding_starts_at" :value="__('Disponible à partir de (optionnel)')" />
                    <x-text-input type="datetime-local" name="onboarding_starts_at" id="onboarding_starts_at"
                        value="{{ optional($companySetting->onboarding_starts_at)->format('Y-m-d\TH:i') }}"
                        class="mt-1" />
                    <x-input-error :messages="$errors->get('onboarding_starts_at')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="onboarding_ends_at" :value="__('Jusqu\'au (optionnel)')" />
                    <x-text-input type="datetime-local" name="onboarding_ends_at" id="onboarding_ends_at"
                        value="{{ optional($companySetting->onboarding_ends_at)->format('Y-m-d\TH:i') }}"
                        class="mt-1" />
                    <x-input-error :messages="$errors->get('onboarding_ends_at')" class="mt-2" />
                </div>
            </div>
            <p class="mt-2 text-xs text-muted">
                {{ __('Laissez ces deux champs vides pour un lien ouvert sans limite de date.') }}</p>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    <form method="GET" action="{{ route('organisation.employees.onboarding-requests.index') }}"
        class="mb-6 max-w-xs">
        <x-select name="status" onchange="this.form.submit()">
            <option value="" @selected($status === '')>{{ __('Tous les statuts') }}</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
    </form>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                        {{ __('Employé') }}</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                        {{ __('Email') }}</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                        {{ __('Téléphone') }}</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                        {{ __('Reçue le') }}</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">
                        {{ __('Statut') }}</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($requests as $onboardingRequest)
                    @php
                        $tones = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    @endphp
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $onboardingRequest->fullName() }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $onboardingRequest->personal_email }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $onboardingRequest->personal_phone ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted"
                            data-sort-value="{{ $onboardingRequest->created_at->timestamp }}">
                            {{ $onboardingRequest->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$tones[$onboardingRequest->status] ?? 'neutral'">
                                {{ $statuses[$onboardingRequest->status] ?? $onboardingRequest->status }}
                            </x-status-chip>
                        </td>
                        <td class="space-x-3 px-6 py-4 text-right text-sm">
                            @if ($onboardingRequest->status === 'pending')
                                <form method="POST"
                                    action="{{ route('organisation.employees.onboarding-requests.approve', $onboardingRequest) }}"
                                    class="inline"
                                    data-confirm="Créer la fiche employé pour {{ $onboardingRequest->fullName() }} ?">
                                    @csrf
                                    <button type="submit"
                                        class="text-success hover:underline">{{ __('Approuver') }}</button>
                                </form>
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'reject-onboarding-{{ $onboardingRequest->id }}')"
                                    class="text-danger hover:underline">{{ __('Refuser') }}</button>
                            @elseif ($onboardingRequest->status === 'approved' && $onboardingRequest->employee)
                                <a href="{{ route('organisation.employees.show', $onboardingRequest->employee) }}"
                                    class="text-brand hover:underline">{{ __('Voir la fiche') }}</a>
                            @elseif ($onboardingRequest->rejection_reason)
                                <span
                                    class="text-xs italic text-muted">{{ $onboardingRequest->rejection_reason }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">
                            {{ __('Aucune demande.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    @foreach ($requests as $onboardingRequest)
        @if ($onboardingRequest->status === 'pending')
            <x-modal name="reject-onboarding-{{ $onboardingRequest->id }}" focusable>
                <form method="POST"
                    action="{{ route('organisation.employees.onboarding-requests.reject', $onboardingRequest) }}"
                    class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-fg">{{ __('Ne pas créer la fiche') }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ $onboardingRequest->fullName() }}</p>

                    <div class="mt-4">
                        <x-input-label for="rejection_reason_{{ $onboardingRequest->id }}" :value="__('Motif du refus (optionnel)')" />
                        <textarea name="rejection_reason" id="rejection_reason_{{ $onboardingRequest->id }}" rows="3"
                            class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button type="button"
                            x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                        <x-danger-button class="ms-3">{{ __('Refuser') }}</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach
</x-app-layout>
