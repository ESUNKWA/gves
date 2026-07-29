<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePayComponent;
use App\Models\PayrollComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeePayComponentController extends Controller
{
    /**
     * Assign several rubriques to this one employee in a single submit — the
     * "Assigner une rubrique" modal is a table of every not-yet-assigned
     * component, each with its own amount field for fixed-method components,
     * instead of picking and submitting one component at a time.
     */
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        $data = $request->validate([
            'payroll_component_ids' => 'required|array|min:1',
            'payroll_component_ids.*' => 'exists:payroll_components,id',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
        ]);

        $components = PayrollComponent::whereIn('id', $data['payroll_component_ids'])->get()->keyBy('id');
        $amounts = $data['amounts'] ?? [];

        $missingAmount = collect($data['payroll_component_ids'])->first(
            fn ($id) => $components->get($id)?->calculation_method === PayrollComponent::METHOD_FIXED
                && ! filled($amounts[$id] ?? null)
        );

        if ($missingAmount) {
            return back()->withErrors([
                'amounts' => 'Un montant est requis pour chaque rubrique à montant fixe sélectionnée.',
            ])->withInput();
        }

        foreach ($data['payroll_component_ids'] as $componentId) {
            $employee->payComponents()->updateOrCreate(
                ['payroll_component_id' => $componentId],
                ['amount' => $amounts[$componentId] ?? null, 'is_active' => true]
            );
        }

        $count = count($data['payroll_component_ids']);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', "{$count} rubrique(s) assignée(s).")
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
}
