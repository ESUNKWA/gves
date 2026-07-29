<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Country;
use App\Models\EmployeeOnboardingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OnboardingRequestController extends Controller
{
    public function create(): View
    {
        return view('onboarding.create', [
            'isOpen' => CompanySetting::current()->isOnboardingOpen(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(CompanySetting::current()->isOnboardingOpen(), 404);

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date|before:today',
            'national_id' => 'nullable|string|max:100',
            'personal_email' => 'required|email|max:255',
            'personal_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => ['nullable', 'string', Rule::in(Country::options())],
            'marital_status' => 'nullable|string|max:50',
        ]);

        EmployeeOnboardingRequest::create($data);

        return redirect()->route('onboarding.thanks');
    }

    public function thanks(): View
    {
        return view('onboarding.thanks');
    }
}
