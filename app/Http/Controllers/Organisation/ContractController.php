<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Payslip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $this->validated($request);
        $data = $this->applySalaryMode($data, $employee);

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('contracts', 'local');
        }

        if ($data['status'] === Contract::STATUS_ACTIVE) {
            $data['signed_at'] = now();
        }

        $employee->contracts()->create($data);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Contrat créé.')
            ->with('open_tab', 'contracts');
    }

    public function update(Request $request, Employee $employee, Contract $contract): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($contract->employee_id === $employee->id, 404);

        $data = $this->validated($request);
        $data = $this->applySalaryMode($data, $employee);

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('contracts', 'local');
        }

        if ($data['status'] === Contract::STATUS_ACTIVE && ! $contract->signed_at) {
            $data['signed_at'] = now();
        }

        $contract->update($data);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Contrat mis à jour.')
            ->with('open_tab', 'contracts');
    }

    public function destroy(Request $request, Employee $employee, Contract $contract): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($contract->employee_id === $employee->id, 404);

        $contract->delete();

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Contrat supprimé.')
            ->with('open_tab', 'contracts');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'contract_type' => 'required|in:'.implode(',', array_keys(Contract::types())),
            'job_title' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'trial_end_date' => 'nullable|date',
            'base_salary' => 'nullable|numeric|min:0',
            'salary_mode' => 'required|in:'.implode(',', array_keys(Contract::salaryModes())),
            'net_salary_target' => 'nullable|required_if:salary_mode,'.Contract::SALARY_MODE_NET.'|numeric|min:0',
            'currency' => 'required|string|max:10',
            'working_hours_per_week' => 'nullable|integer|min:0|max:168',
            'status' => 'required|in:'.implode(',', array_keys(Contract::statuses())),
            'notes' => 'nullable|string',
            'document' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        unset($data['document']);

        return $data;
    }

    /**
     * When the contract is negotiated in NET (salary_mode = net), base_salary
     * isn't user-entered — it's derived from net_salary_target via
     * Payslip::solveGrossForNet() using the employee's currently assigned pay
     * components (best-effort until those are assigned; kept accurate
     * afterwards by generateFor() re-solving it on every payslip run).
     * Switching back to GROSS clears the stale net target.
     */
    private function applySalaryMode(array $data, Employee $employee): array
    {
        if ($data['salary_mode'] === Contract::SALARY_MODE_NET) {
            $data['base_salary'] = Payslip::solveGrossForNet($employee, (float) $data['net_salary_target']);
        } else {
            $data['net_salary_target'] = null;
        }

        return $data;
    }
}
