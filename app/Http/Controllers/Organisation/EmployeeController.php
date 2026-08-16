<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Contract;
use App\Models\Country;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeePayComponent;
use App\Models\GeneratedDocument;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollComponent;
use App\Models\Position;
use App\Models\Site;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->with(['site', 'department', 'position'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('site_id'), fn ($query, $siteId) => $query->where('site_id', $siteId))
            ->when($request->integer('department_id'), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->orderBy('last_name')
            ->get();

        return view('organisation.employees.index', [
            'employees' => $employees,
            'status' => $request->string('status')->toString(),
            'site_id' => $request->integer('site_id'),
            'department_id' => $request->integer('department_id'),
            'sites' => Site::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'statuses' => Employee::statuses(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('employees.manage'), 403);

        return view('organisation.employees.create', [
            'employee_number' => Employee::nextEmployeeNumber(),
            'sites' => Site::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('title')->get(),
            'managers' => Employee::orderBy('first_name')->get(),
            'statuses' => Employee::statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $request->validate($this->rules($request));

        $employee = Employee::create($data);

        PayrollComponent::assignDefaultsTo($employee);

        return redirect()->route('organisation.employees.show', $employee)->with('status', 'Employé créé.');
    }

    public function show(Employee $employee): View
    {
        $employee->load(['site', 'department', 'position', 'manager', 'latestContract']);

        $year = now()->year;
        $activeLeaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();
        $leaveBalances = $activeLeaveTypes->map(fn (LeaveType $type) => LeaveBalance::forEmployee($employee, $type, $year));

        return view('organisation.employees.show', [
            'employee' => $employee,
            'contracts' => $employee->contracts()->orderByDesc('start_date')->get(),
            'positions' => Position::orderBy('title')->get(),
            'documents' => $employee->documents()->latest('uploaded_at')->get(),
            'contractTypes' => Contract::types(),
            'contractStatuses' => Contract::statuses(),
            'documentCategories' => EmployeeDocument::categories(),
            'leaveBalances' => $leaveBalances,
            'leaveTypes' => $activeLeaveTypes,
            'leaveRequests' => $employee->leaveRequests()->with('leaveType')->latest('start_date')->get(),
            'leaveStatuses' => LeaveRequest::statuses(),
            'generatedDocuments' => $employee->generatedDocuments()->latest()->get(),
            'documentTemplates' => DocumentTemplate::where('is_active', true)->orderBy('name')->get(),
            'signatureStatuses' => GeneratedDocument::statuses(),
            'workSchedule' => $employee->effectiveWorkSchedule(),
            'hasOwnWorkSchedule' => (bool) $employee->workSchedule,
            'timeEntries' => $employee->timeEntries()->latest('date')->limit(30)->get(),
            'dayLabels' => WorkSchedule::dayLabels(),
            'employeePayComponents' => $employee->payComponents()->with('payrollComponent')->get()
                ->sortBy(fn (EmployeePayComponent $a) => $a->payrollComponent->order ?? 0),
            'availablePayrollComponents' => PayrollComponent::where('is_active', true)
                ->whereNotIn('id', $employee->payComponents()->pluck('payroll_component_id'))
                ->orderBy('order')
                ->get(),
            'payslips' => $employee->payslips()->latest('period')->get(),
            'company' => CompanySetting::current(),
        ]);
    }

    public function edit(Employee $employee): View
    {
        abort_unless(auth()->user()->can('employees.manage'), 403);

        return view('organisation.employees.edit', [
            'employee' => $employee,
            'sites' => Site::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'positions' => Position::orderBy('title')->get(),
            'managers' => Employee::whereKeyNot($employee->id)->orderBy('first_name')->get(),
            'statuses' => Employee::statuses(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $request->validate($this->rules($request, $employee) + [
            'termination_date' => 'nullable|date',
        ]);

        $employee->update($data);

        return redirect()->route('organisation.employees.show', $employee)->with('status', 'Employé mis à jour.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $employee->delete();

        return redirect()->route('organisation.employees.index')->with('status', 'Employé supprimé.');
    }

    public function anonymize(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.anonymize'), 403);

        $employee->anonymize();

        return redirect()
            ->route('organisation.employees.show', $employee)
            ->with('status', 'Les données personnelles de cet employé ont été anonymisées conformément au RGPD.');
    }

    private function rules(Request $request, ?Employee $employee = null): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_number')->ignore($employee?->id)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'nationality' => ['nullable', 'string', Rule::in(Country::options($employee?->nationality))],
            'national_id' => 'nullable|string|max:100',
            'personal_email' => 'nullable|email|max:255',
            'personal_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => ['nullable', 'string', Rule::in(Country::options($employee?->country))],
            'bank_account_number' => 'nullable|string|max:50',
            'social_security_number' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'qualification' => 'nullable|string|max:100',
            'tax_shares' => 'nullable|numeric|min:0|max:99.99',
            'marital_status' => 'nullable|string|max:50',
            'site_id' => 'nullable|exists:sites,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'manager_id' => 'nullable|exists:employees,id',
            'hire_date' => 'required|date',
            'status' => 'required|in:'.implode(',', array_keys(Employee::statuses())),
        ];
    }
}
