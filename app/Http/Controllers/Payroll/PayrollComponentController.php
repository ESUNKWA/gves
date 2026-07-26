<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayrollComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollComponentController extends Controller
{
    public function index(): View
    {
        return view('payroll.components.index', [
            'components' => PayrollComponent::with('baseComponent')
                ->withCount(['employeeAssignments', 'payslipLines'])
                ->orderBy('order')
                ->get(),
            'types' => PayrollComponent::types(),
            'methods' => PayrollComponent::calculationMethods(),
            'deductionCategories' => PayrollComponent::deductionCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        PayrollComponent::create($this->validated($request));

        return redirect()->route('payroll.components.index')->with('status', 'Rubrique créée.');
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
                ->with('error', "Cette rubrique est déjà utilisée (assignée à des employés ou présente sur des bulletins) et ne peut pas être supprimée. Désactivez-la à la place.");
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
