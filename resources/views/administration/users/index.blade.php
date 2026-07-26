<x-app-layout>
    <x-page-header :title="__('Utilisateurs & rôles')" :description="__('Gérez le rôle de chaque compte utilisateur.')" />

    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-success-soft text-success text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($users as $user)
                    @php $role = $user->getRoleNames()->first(); @endphp
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            {{ $user->employee?->full_name ?? $user->name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if ($role)
                                <x-status-chip tone="neutral">{{ $roleLabels[$role] ?? $role }}</x-status-chip>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            @if ($user->id === auth()->id())
                                <span class="text-xs text-muted italic">Vous (non modifiable)</span>
                            @else
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'user-edit-{{ $user->id }}')"
                                    class="text-brand hover:underline">Modifier le rôle</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-muted">Aucun utilisateur pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    @foreach ($users as $user)
        @continue($user->id === auth()->id())
        <x-modal name="user-edit-{{ $user->id }}" focusable>
            <form method="POST" action="{{ route('administration.users.update', $user) }}" class="p-6">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le rôle') }}</h2>
                <p class="mt-1 text-sm text-muted">
                    {{ $user->employee?->full_name ?? $user->name }} — {{ $user->email }}
                </p>

                <div class="mt-6">
                    <x-input-label for="role_{{ $user->id }}" value="Rôle" />
                    <x-select name="role" id="role_{{ $user->id }}" class="mt-1">
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption }}" @selected($user->getRoleNames()->first() === $roleOption)>
                                {{ $roleLabels[$roleOption] ?? $roleOption }}</option>
                        @endforeach
                    </x-select>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button"
                        x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-app-layout>
