<x-app-layout>
    <x-page-header :title="__('Types de congés')" :description="__('Configurez les types de congés, leurs règles d\'acquisition et d\'approbation.')">
        <x-slot:actions>
            @can('leaves.manage')
                <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'leave-type-create')">
                    {{ __('Nouveau type') }}
                </x-primary-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Nom</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Acquisition</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Rémunéré</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Approbation</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($leaveTypes as $leaveType)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $leaveType->name }}
                            <span class="text-muted">({{ $leaveType->code }})</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">
                            @if ($leaveType->accruesAutomatically())
                                {{ rtrim(rtrim(number_format($leaveType->accrual_days_per_month, 2), '0'), '.') }}
                                j/mois
                            @else
                                Manuelle
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $leaveType->is_paid ? 'Oui' : 'Non' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ $leaveType->requires_approval ? 'Requise' : 'Auto' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if ($leaveType->is_active)
                                <x-status-chip tone="success">Actif</x-status-chip>
                            @else
                                <x-status-chip tone="neutral">Inactif</x-status-chip>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            @can('leaves.manage')
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'leave-type-edit-{{ $leaveType->id }}')"
                                    class="text-brand hover:underline">Modifier</button>

                                @if ($leaveType->leave_requests_count === 0 && $leaveType->leave_balances_count === 0)
                                    <form method="POST" action="{{ route('leaves.types.destroy', $leaveType) }}"
                                        class="inline" data-confirm="Supprimer ce type de congé ?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Aucun type de congé pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    <x-modal name="leave-type-create" :show="old('_modal') === 'leave-type-create'" focusable>
        <form method="POST" action="{{ route('leaves.types.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="leave-type-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouveau type de congé') }}</h2>

            @include('leaves.types.partials.fields', [
                'id' => 'create',
                'leaveType' => null,
                'showErrors' => old('_modal') === 'leave-type-create',
            ])

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($leaveTypes as $leaveType)
        @php $isEditingThis = old('_modal') === 'leave-type-edit-'.$leaveType->id; @endphp
        <x-modal name="leave-type-edit-{{ $leaveType->id }}" :show="$isEditingThis" focusable>
            <form method="POST" action="{{ route('leaves.types.update', $leaveType) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="leave-type-edit-{{ $leaveType->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le type de congé') }}</h2>

                @include('leaves.types.partials.fields', [
                    'id' => 'edit-' . $leaveType->id,
                    'leaveType' => $leaveType,
                    'showErrors' => $isEditingThis,
                ])

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button"
                        x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-app-layout>
