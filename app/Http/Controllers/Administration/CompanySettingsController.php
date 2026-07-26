<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        return view('administration.company-settings.edit', [
            'settings' => CompanySetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = CompanySetting::current();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'primary_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'currency' => 'required|string|max:10',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => ['nullable', 'string', Rule::in(Country::options($settings->country))],
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'social_security_number' => 'nullable|string|max:100',
            'collective_agreement' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        unset($data['logo']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
        }

        $settings->update($data);

        return redirect()->route('administration.company.edit')->with('status', 'Informations enregistrées.');
    }
}
