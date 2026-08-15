<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePayComponent;
use App\Models\PayrollComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollComponentController extends Controller
{
    public function index(): View
    {
        $components = PayrollComponent::with('baseComponent')
            ->withCount(['employeeAssignments', 'payslipLines'])
            ->orderBy('order')
            ->get();

        return view('payroll.components.index', [
            'components' => $components,
            'types' => PayrollComponent::types(),
            'methods' => PayrollComponent::calculationMethods(),
            'deductionCategories' => PayrollComponent::deductionCategories(),
            'employees' => Employee::where('status', Employee::STATUS_ACTIVE)
                ->with('latestContract')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'employee_number']),
            'assignedEmployeeIdsByComponent' => EmployeePayComponent::whereIn('payroll_component_id', $components->pluck('id'))
                ->get(['payroll_component_id', 'employee_id'])
                ->groupBy('payroll_component_id')
                ->map(fn ($rows) => $rows->pluck('employee_id')),
        ]);
    }

    /**
     * Assign this component to every selected employee in one go, instead of
     * one-by-one from each employee's own "Structure de rémunération" tab —
     * mirrors EmployeePayComponentController::store()'s table (one row per
     * employee, its own amount field for fixed-method components).
     * updateOrCreate keeps this idempotent for anyone already assigned rather
     * than tripping the (employee_id, payroll_component_id) unique constraint.
     */
    public function bulkAssign(Request $request, PayrollComponent $payrollComponent): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        $data = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
        ]);

        $amounts = $data['amounts'] ?? [];

        if ($payrollComponent->calculation_method === PayrollComponent::METHOD_FIXED) {
            $missingAmount = collect($data['employee_ids'])->first(
                fn ($id) => ! filled($amounts[$id] ?? null)
            );

            if ($missingAmount) {
                return back()->withErrors([
                    'amounts' => 'Un montant est requis pour chaque employé sélectionné.',
                ])->withInput();
            }
        }

        foreach ($data['employee_ids'] as $employeeId) {
            EmployeePayComponent::updateOrCreate(
                ['employee_id' => $employeeId, 'payroll_component_id' => $payrollComponent->id],
                ['amount' => $amounts[$employeeId] ?? null, 'is_active' => true]
            );
        }

        $count = count($data['employee_ids']);

        return redirect()->route('payroll.components.index')
            ->with('status', "Rubrique assignée à {$count} employé(s).");
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        PayrollComponent::create($this->validated($request));

        return redirect()->route('payroll.components.index')->with('status', 'Rubrique créée.');
    }

    /**
     * Persist a new drag-and-drop order from the rubriques list — called via
     * AJAX (see resources/views/payroll/components/index.blade.php's
     * x-sort handler), one request per drop, no page reload/redirect.
     */
    public function reorder(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:payroll_components,id',
        ]);

        foreach ($data['order'] as $position => $id) {
            PayrollComponent::whereKey($id)->update(['order' => $position + 1]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function update(Request $request, PayrollComponent $payrollComponent): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        $payrollComponent->update($this->validated($request, $payrollComponent));

        return redirect()->route('payroll.components.index')->with('status', 'Rubrique mise à jour.');
    }

    public function destroy(Request $request, PayrollComponent $payrollComponent): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        if ($payrollComponent->employeeAssignments()->exists() || $payrollComponent->payslipLines()->exists()) {
            return redirect()->route('payroll.components.index')
                ->with('error', 'Cette rubrique est déjà utilisée (assignée à des employés ou présente sur des bulletins) et ne peut pas être supprimée. Désactivez-la à la place.');
        }

        $payrollComponent->delete();

        return redirect()->route('payroll.components.index')->with('status', 'Rubrique supprimée.');
    }

    private function validated(Request $request, ?PayrollComponent $payrollComponent = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('payroll_components', 'code')->ignore($payrollComponent?->id)],
            'type' => 'required|string|in:'.implode(',', array_keys(PayrollComponent::types())),
            'calculation_method' => 'required|string|in:'.implode(',', array_keys(PayrollComponent::calculationMethods())),
            'base_component_id' => [
                'nullable',
                'exists:payroll_components,id',
                Rule::requiredIf($request->input('calculation_method') === PayrollComponent::METHOD_PERCENTAGE_OF_COMPONENT),
                Rule::notIn([$payrollComponent?->id]),
            ],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:100', Rule::requiredIf($request->input('calculation_method') !== PayrollComponent::METHOD_FIXED)],
            'ceiling_amount' => 'nullable|numeric|min:0',
            'deduction_category' => 'nullable|string|in:'.implode(',', array_keys(PayrollComponent::deductionCategories())),
            'order' => 'required|integer|min:0',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_base_salary'] = $request->boolean('is_base_salary');
        $data['is_subject_to_contributions'] = $request->boolean('is_subject_to_contributions', true);

        if ($data['calculation_method'] === PayrollComponent::METHOD_FIXED) {
            $data['base_component_id'] = null;
            $data['rate'] = null;
        } elseif ($data['calculation_method'] === PayrollComponent::METHOD_PERCENTAGE_OF_GROSS) {
            $data['base_component_id'] = null;
        }

        if ($data['type'] !== PayrollComponent::TYPE_DEDUCTION) {
            $data['deduction_category'] = null;
        }

        if ($data['type'] !== PayrollComponent::TYPE_GAIN) {
            $data['is_base_salary'] = false;
            $data['is_subject_to_contributions'] = true;
        }

        return $data;
    }
}
