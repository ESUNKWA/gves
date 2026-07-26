<x-app-layout>
    <x-page-header :title="__('Mon profil')" :description="__('Vos informations personnelles.')" />

    @if (session('status'))
        <div class="mb-6 p-3 rounded-lg bg-success-soft text-success text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6 lg:col-span-1 h-fit">
            <h3 class="text-base font-semibold text-fg">{{ $employee->full_name }}</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-xs uppercase text-muted">Matricule</dt>
                    <dd class="text-fg">{{ $employee->employee_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Poste</dt>
                    <dd class="text-fg">{{ $employee->position?->title ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Département</dt>
                    <dd class="text-fg">{{ $employee->department?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Site</dt>
                    <dd class="text-fg">{{ $employee->site?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Manager</dt>
                    <dd class="text-fg">{{ $employee->manager?->full_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-muted">Date d'embauche</dt>
                    <dd class="text-fg">{{ $employee->hire_date->format('d/m/Y') }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-muted">
                Ces informations sont gérées par les ressources humaines. Contactez-les pour toute correction.
            </p>
        </div>

        <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6 lg:col-span-2">
            <h3 class="text-base font-semibold text-fg">Coordonnées</h3>
            <p class="mt-1 text-sm text-muted">Ces informations sont modifiables par vos soins.</p>

            <form method="POST" action="{{ route('portal.profile.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="personal_email" value="Email personnel" />
                        <x-text-input name="personal_email" id="personal_email"
                            value="{{ old('personal_email', $employee->personal_email) }}" type="email"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('personal_email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="personal_phone" value="Téléphone" />
                        <x-text-input name="personal_phone" id="personal_phone"
                            value="{{ old('personal_phone', $employee->personal_phone) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('personal_phone')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Adresse" />
                        <x-text-input name="address" id="address" value="{{ old('address', $employee->address) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="city" value="Ville" />
                        <x-text-input name="city" id="city" value="{{ old('city', $employee->city) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="country" value="Pays" />
                        <x-country-select name="country" id="country" :selected="old('country', $employee->country)" class="mt-1" />
                        <x-input-error :messages="$errors->get('country')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
