<x-app-layout>
    <x-page-header :title="__('Modifier :name', ['name' => $employee->full_name])" :description="__('Mettez à jour les informations de cet employé.')" />

    <div class="max-w-4xl">
        <form method="POST" action="{{ route('organisation.employees.update', $employee) }}"
            class="rounded-xl border border-line-soft bg-surface shadow-card p-6 space-y-8">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-base font-semibold text-fg">Identité</h3>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="employee_number" value="Matricule" />
                        <x-text-input name="employee_number" id="employee_number"
                            value="{{ old('employee_number', $employee->employee_number) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('employee_number')" class="mt-2" />
                    </div>
                    <div></div>

                    <div>
                        <x-input-label for="first_name" value="Prénom" />
                        <x-text-input name="first_name" id="first_name"
                            value="{{ old('first_name', $employee->first_name) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="last_name" value="Nom" />
                        <x-text-input name="last_name" id="last_name"
                            value="{{ old('last_name', $employee->last_name) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="gender" value="Genre" />
                        <x-select name="gender" id="gender" class="mt-1">
                            <option value="">—</option>
                            <option value="male" @selected(old('gender', $employee->gender) === 'male')>Masculin</option>
                            <option value="female" @selected(old('gender', $employee->gender) === 'female')>Féminin</option>
                            <option value="other" @selected(old('gender', $employee->gender) === 'other')>Autre</option>
                        </x-select>
                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="birth_date" value="Date de naissance" />
                        <x-text-input name="birth_date" id="birth_date"
                            value="{{ old('birth_date', $employee->birth_date?->toDateString()) }}" type="date"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="national_id" value="N° pièce d'identité" />
                        <x-text-input name="national_id" id="national_id"
                            value="{{ old('national_id', $employee->national_id) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('national_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="marital_status" value="Situation familiale" />
                        <x-text-input name="marital_status" id="marital_status"
                            value="{{ old('marital_status', $employee->marital_status) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('marital_status')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold text-fg">Coordonnées</h3>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                    <div>
                        <x-input-label for="bank_account_number" value="Compte bancaire (IBAN/n° de compte)" />
                        <x-text-input name="bank_account_number" id="bank_account_number"
                            value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('bank_account_number')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold text-fg">Informations de paie</h3>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="social_security_number" value="N° de sécurité sociale (CNPS, etc.)" />
                        <x-text-input name="social_security_number" id="social_security_number"
                            value="{{ old('social_security_number', $employee->social_security_number) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('social_security_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="category" value="Catégorie professionnelle" />
                        <x-text-input name="category" id="category"
                            value="{{ old('category', $employee->category) }}" placeholder="ex : Cadre Classe 4.3"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="qualification" value="Qualification" />
                        <x-text-input name="qualification" id="qualification"
                            value="{{ old('qualification', $employee->qualification) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('qualification')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tax_shares" value="Parts fiscales" />
                        <x-text-input name="tax_shares" id="tax_shares" type="number" step="0.01"
                            min="0" value="{{ old('tax_shares', $employee->tax_shares) }}" class="mt-1" />
                        <x-input-error :messages="$errors->get('tax_shares')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold text-fg">Organisation</h3>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="site_id" value="Site" />
                        <x-select name="site_id" id="site_id" class="mt-1">
                            <option value="">—</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" @selected(old('site_id', $employee->site_id) == $site->id)>{{ $site->name }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('site_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="department_id" value="Département" />
                        <x-select name="department_id" id="department_id" class="mt-1">
                            <option value="">—</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id) == $department->id)>
                                    {{ $department->name }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="position_id" value="Poste" />
                        <x-select name="position_id" id="position_id" class="mt-1">
                            <option value="">—</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}" @selected(old('position_id', $employee->position_id) == $position->id)>
                                    {{ $position->title }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('position_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="manager_id" value="Manager" />
                        <x-select name="manager_id" id="manager_id" class="mt-1">
                            <option value="">—</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" @selected(old('manager_id', $employee->manager_id) == $manager->id)>
                                    {{ $manager->full_name }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="hire_date" value="Date d'embauche" />
                        <x-text-input name="hire_date" id="hire_date"
                            value="{{ old('hire_date', $employee->hire_date->toDateString()) }}" type="date"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('hire_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status" value="Statut" />
                        <x-select name="status" id="status" class="mt-1">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $employee->status) === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="termination_date" value="Date de sortie" />
                        <x-text-input name="termination_date" id="termination_date"
                            value="{{ old('termination_date', $employee->termination_date?->toDateString()) }}"
                            type="date" class="mt-1" />
                        <x-input-error :messages="$errors->get('termination_date')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('organisation.employees.show', $employee) }}">
                    <x-secondary-button type="button">{{ __('Annuler') }}</x-secondary-button>
                </a>
                <x-primary-button>{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
