<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\EmployeeOnboardingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeOnboardingRequestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $status = $request->input('status', EmployeeOnboardingRequest::STATUS_PENDING);

        return view('organisation.onboarding-requests.index', [
            'requests' => EmployeeOnboardingRequest::with('reviewedBy')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->get(),
            'statuses' => EmployeeOnboardingRequest::statuses(),
            'status' => $status,
            'companySetting' => CompanySetting::current(),
        ]);
    }

    public function approve(Request $request, EmployeeOnboardingRequest $onboardingRequest): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($onboardingRequest->status === EmployeeOnboardingRequest::STATUS_PENDING, 400);

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => $onboardingRequest->first_name,
            'last_name' => $onboardingRequest->last_name,
            'gender' => $onboardingRequest->gender,
            'birth_date' => $onboardingRequest->birth_date,
            'national_id' => $onboardingRequest->national_id,
            'personal_email' => $onboardingRequest->personal_email,
            'personal_phone' => $onboardingRequest->personal_phone,
            'address' => $onboardingRequest->address,
            'city' => $onboardingRequest->city,
            'country' => $onboardingRequest->country,
            'marital_status' => $onboardingRequest->marital_status,
            'hire_date' => now(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $onboardingRequest->forceFill([
            'status' => EmployeeOnboardingRequest::STATUS_APPROVED,
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at' => now(),
            'employee_id' => $employee->id,
        ])->save();

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Fiche employé créée à partir des informations transmises. Complétez le site, le département et le poste.');
    }

    public function reject(Request $request, EmployeeOnboardingRequest $onboardingRequest): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($onboardingRequest->status === EmployeeOnboardingRequest::STATUS_PENDING, 400);

        $data = $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $onboardingRequest->forceFill([
            'status' => EmployeeOnboardingRequest::STATUS_REJECTED,
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ])->save();

        return redirect()->route('organisation.employees.onboarding-requests.index')
            ->with('status', 'Informations écartées — aucune fiche employé créée.');
    }
}
