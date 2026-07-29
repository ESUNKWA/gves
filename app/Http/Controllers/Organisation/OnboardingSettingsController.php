<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $request->validate([
            'onboarding_enabled' => 'nullable|boolean',
            'onboarding_starts_at' => 'nullable|date',
            'onboarding_ends_at' => 'nullable|date|after_or_equal:onboarding_starts_at',
        ]);

        $data['onboarding_enabled'] = $request->boolean('onboarding_enabled');

        CompanySetting::current()->update($data);

        return redirect()->route('organisation.employees.onboarding-requests.index')
            ->with('status', 'Paramètres du lien mis à jour.');
    }
}
