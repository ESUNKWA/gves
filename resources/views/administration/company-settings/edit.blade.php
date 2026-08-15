<x-app-layout>
    <x-page-header :title="__('Entreprise')" :description="__('Informations générales de votre entreprise.')" />

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('administration.company.update') }}" enctype="multipart/form-data"
            class="rounded-xl border border-line-soft bg-surface shadow-card p-6 space-y-8">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-base font-semibold text-fg">Identité</h3>
                <div class="mt-4 flex items-center gap-4">
                    <span
                        class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-2">
                        @if ($settings->logoUrl())
                            <img src="{{ $settings->logoUrl() }}" alt="{{ $settings->name }}"
                                class="h-full w-full object-contain">
                        @else
                            <svg class="h-8 w-8 text-faint" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        @endif
                    </span>
                    <div class="flex-1">
                        <x-input-label for="logo" value="Logo" />
                        <input name="logo" id="logo" type="file" accept="image/*"
                            class="mt-1 block w-full text-sm text-muted">
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name" value="Nom commercial" />
                        <x-text-input name="name" id="name" value="{{ old('name', $settings->name) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="legal_name" value="Raison sociale" />
                        <x-text-input name="legal_name" id="legal_name"
                            value="{{ old('legal_name', $settings->legal_name) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('legal_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="currency" value="Devise par défaut" />
                        <x-text-input name="currency" id="currency" value="{{ old('currency', $settings->currency) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                    </div>

                    <div x-data="{ color: '{{ old('primary_color', $settings->primary_color) }}' }">
                        <x-input-label for="primary_color_text" value="Couleur d'accent" />
                        <div class="mt-1 flex items-center gap-3">
                            <input type="color" x-model="color" aria-label="Couleur d'accent"
                                class="h-10 w-14 shrink-0 cursor-pointer rounded-lg border border-line bg-surface p-1">
                            <input type="text" name="primary_color" id="primary_color_text" x-model="color"
                                maxlength="7" placeholder="#f990a5"
                                class="block w-full rounded-lg border-line bg-surface text-sm font-mono text-fg shadow-sm focus:border-brand focus:ring-brand dark:focus:border-brand dark:focus:ring-brand">
                        </div>
                        <p class="mt-1 text-xs text-muted">
                            {{ __('Utilisée pour les boutons, liens et éléments actifs dans toute l\'application.') }}
                        </p>
                        <x-input-error :messages="$errors->get('primary_color')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold text-fg">Coordonnées</h3>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="email" value="Email de contact" />
                        <x-text-input name="email" id="email" type="email"
                            value="{{ old('email', $settings->email) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="phone" value="Téléphone" />
                        <x-text-input name="phone" id="phone" value="{{ old('phone', $settings->phone) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="address" value="Adresse du siège" />
                        <x-text-input name="address" id="address" value="{{ old('address', $settings->address) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="city" value="Ville" />
                        <x-text-input name="city" id="city" value="{{ old('city', $settings->city) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('city')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="country" value="Pays" />
                        <x-country-select name="country" id="country" :selected="old('country', $settings->country)" class="mt-1" />
                        <x-input-error :messages="$errors->get('country')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold text-fg">Informations légales</h3>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="registration_number" value="N° RCCM (ou équivalent)" />
                        <x-text-input name="registration_number" id="registration_number"
                            value="{{ old('registration_number', $settings->registration_number) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tax_id" value="N° d'identification fiscale" />
                        <x-text-input name="tax_id" id="tax_id" value="{{ old('tax_id', $settings->tax_id) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="social_security_number"
                            value="N° de sécurité sociale employeur (CNPS, etc.)" />
                        <x-text-input name="social_security_number" id="social_security_number"
                            value="{{ old('social_security_number', $settings->social_security_number) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('social_security_number')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="collective_agreement" value="Convention collective" />
                        <x-text-input name="collective_agreement" id="collective_agreement"
                            value="{{ old('collective_agreement', $settings->collective_agreement) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('collective_agreement')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-primary-button>{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
