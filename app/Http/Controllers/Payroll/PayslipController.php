<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->filled('period')
            ? Carbon::parse($request->string('period').'-01')
            : now()->startOfMonth();

        $status = $request->string('status')->toString();

        $payslips = Payslip::with('employee')
            ->whereYear('period', $period->year)
            ->whereMonth('period', $period->month)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->get()
            ->sortBy(fn (Payslip $p) => $p->employee->full_name);

        return view('payroll.payslips.index', [
            'payslips' => $payslips,
            'period' => $period,
            'status' => $status,
            'statuses' => Payslip::statuses(),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        $data = $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        $period = Carbon::parse($data['period'].'-01');

        $employees = Employee::where('status', Employee::STATUS_ACTIVE)
            ->whereHas('payComponents', fn ($q) => $q->where('is_active', true))
            ->get();

        $alreadyValidated = Payslip::whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('period', $period->toDateString())
            ->where('status', Payslip::STATUS_VALIDATED)
            ->pluck('employee_id');

        $count = 0;

        foreach ($employees as $employee) {
            if ($alreadyValidated->contains($employee->id)) {
                continue;
            }

            Payslip::generateFor($employee, $period, $request->user()->id);
            $count++;
        }

        $status = "Paie générée pour {$count} employé(s) — ".$period->translatedFormat('F Y').'.';

        if ($alreadyValidated->isNotEmpty()) {
            $status .= ' '.$alreadyValidated->count().' bulletin(s) déjà validé(s) ont été laissés inchangés.';
        }

        return redirect()->route('payroll.payslips.index', ['period' => $period->format('Y-m')])
            ->with('status', $status);
    }

    public function show(Payslip $payslip): View
    {
        return view('payroll.payslips.show', [
            'payslip' => $payslip->load(['employee', 'lines']),
        ]);
    }

    public function addLine(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);
        abort_unless($payslip->status === Payslip::STATUS_DRAFT, 400, 'Ce bulletin est déjà validé.');

        $data = $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', [PayrollComponent::TYPE_GAIN, PayrollComponent::TYPE_DEDUCTION, PayrollComponent::TYPE_EMPLOYER_CHARGE]),
            'amount' => 'required|numeric|min:0',
        ]);

        $payslip->lines()->create($data);

        $gross = $payslip->lines()->where('type', PayrollComponent::TYPE_GAIN)->sum('amount');
        $deductions = $payslip->lines()->where('type', PayrollComponent::TYPE_DEDUCTION)->sum('amount');
        $employerCharges = $payslip->lines()->where('type', PayrollComponent::TYPE_EMPLOYER_CHARGE)->sum('amount');

        $payslip->update([
            'gross_amount' => $gross,
            'deductions_amount' => $deductions,
            'employer_charges_amount' => $employerCharges,
            'net_amount' => $gross - $deductions,
        ]);

        return redirect()->route('payroll.payslips.show', $payslip)->with('status', 'Ligne ajoutée.');
    }

    public function recalculate(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);

        Payslip::generateFor($payslip->employee, $payslip->period, $request->user()->id);

        return redirect()->route('payroll.payslips.show', $payslip)->with('status', 'Bulletin recalculé à partir de la structure de rémunération.');
    }

    public function validatePayslip(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);
        abort_unless($payslip->status === Payslip::STATUS_DRAFT, 400);

        $data = $request->validate([
            'payment_method' => 'required|string|max:255',
            'payment_date' => 'required|date',
        ]);

        $payslip->update([
            'status' => Payslip::STATUS_VALIDATED,
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
            'payment_method' => $data['payment_method'],
            'payment_date' => $data['payment_date'],
        ]);

        $payslip->load(['employee.department', 'employee.site', 'employee.position', 'employee.workSchedule', 'lines.payrollComponent']);

        $pdf = Pdf::loadView('payroll.pdf', [
            'payslip' => $payslip,
            'employee' => $payslip->employee,
            'company' => CompanySetting::current(),
            'attendance' => $payslip->attendanceSummary(),
            'leave' => $payslip->leaveSummary(),
            'yearToDate' => $payslip->yearToDateSummary(),
        ]);

        $path = 'payslips/'.$payslip->employee_id.'/'.$payslip->period->format('Y-m').'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        $payslip->update(['pdf_path' => $path]);

        return redirect()->route('payroll.payslips.show', $payslip)->with('status', 'Bulletin validé et archivé.');
    }

    public function destroy(Request $request, Payslip $payslip): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.manage'), 403);
        abort_unless($payslip->status === Payslip::STATUS_DRAFT, 400, 'Un bulletin validé ne peut pas être supprimé.');

        $payslip->delete();

        return redirect()->route('payroll.payslips.index')->with('status', 'Bulletin supprimé.');
    }

    public function pdf(Request $request, Payslip $payslip)
    {
        abort_unless($request->user()->can('payroll.manage'), 403);
        abort_unless($payslip->status === Payslip::STATUS_VALIDATED && $payslip->pdf_path, 404);

        return Storage::disk('local')->response($payslip->pdf_path, 'Bulletin-'.$payslip->period->format('Y-m').'.pdf');
    }
}
