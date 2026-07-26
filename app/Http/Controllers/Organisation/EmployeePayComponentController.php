<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePayComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeePayComponentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        $data = $this->validated($request);

        $employee->payComponents()->updateOrCreate(
            ['payroll_component_id' => $data['payroll_component_id']],
            ['amount' => $data['amount'] ?? null, 'is_active' => true]
        );

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Rubrique assignée.')
            ->with('open_tab', 'payroll');
    }

    public function update(Request $request, Employee $employee, EmployeePayComponent $employeePayComponent): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);
        abort_unless($employeePayComponent->employee_id === $employee->id, 404);

        $data = $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        $employeePayComponent->update([
            'amount' => $data['amount'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Rubrique mise à jour.')
            ->with('open_tab', 'payroll');
    }

    public function destroy(Request $request, Employee $employee, EmployeePayComponent $employeePayComponent): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);
        abort_unless($employeePayComponent->employee_id === $employee->id, 404);

        $employeePayComponent->delete();

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Rubrique retirée.')
            ->with('open_tab', 'payroll');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'payroll_component_id' => 'required|exists:payroll_components,id',
            'amount' => 'nullable|numeric|min:0',
        ]);
    }
}
