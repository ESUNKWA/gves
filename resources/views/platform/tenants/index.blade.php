<x-platform-layout>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-semibold text-fg">Tenants</h1>
            <p class="mt-1 text-sm text-muted">Clients provisionnés sur cette plateforme.</p>
        </div>
        <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'tenant-create')">
            Nouveau tenant
        </x-primary-button>
    </div>

    <div class="overflow-hidden rounded-lg border border-line bg-surface">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Domaine(s)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Créé le</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($tenants as $tenant)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $tenant->name }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $tenant->slug }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ $tenant->domains->pluck('domain')->join(', ') ?: '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $tenant->status }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $tenant->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <form method="POST" action="{{ route('platform.tenants.resend-welcome', $tenant) }}"
                                data-confirm="Renvoyer l'email de création (nouveau lien de mot de passe) à l'admin de ce tenant ?">
                                @csrf
                                <button type="submit" class="text-brand hover:underline">Renvoyer l'email</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Aucun tenant pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal name="tenant-create" :show="old('_modal') === 'tenant-create'" focusable>
        <form method="POST" action="{{ route('platform.tenants.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="tenant-create">

            <h2 class="text-lg font-medium text-fg">Nouveau tenant</h2>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="name" value="Nom du client" />
                    <x-text-input name="name" id="name" value="{{ old('name') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="slug" value="Slug" />
                    <x-text-input name="slug" id="slug" value="{{ old('slug') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="domain" value="Domaine (ex: demo.sirh.test)" />
                    <x-text-input name="domain" id="domain" value="{{ old('domain') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                </div>

                <div class="sm:col-span-2 border-t border-line-soft pt-4 mt-2">
                    <p class="text-sm font-medium text-fg">Premier compte super-admin de ce tenant</p>
                </div>

                <div>
                    <x-input-label for="admin_name" value="Nom" />
                    <x-text-input name="admin_name" id="admin_name" value="{{ old('admin_name') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="admin_email" value="Email" />
                    <x-text-input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}"
                        class="mt-1" />
                    <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
                    <p class="mt-1 text-xs text-muted">Un email lui sera envoyé pour définir son mot de passe — pas
                        besoin de le saisir ici.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Annuler</x-secondary-button>
                <x-primary-button class="ms-3">Créer</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-platform-layout>
