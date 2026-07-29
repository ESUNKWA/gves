<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('portal.payslips.index', [
            'payslips' => $employee->payslips()
                ->where('status', Payslip::STATUS_VALIDATED)
                ->orderByDesc('period')
                ->get(),
        ]);
    }

    public function view(Request $request, Payslip $payslip)
    {
        $this->authorizeAccess($request, $payslip);

        return Storage::disk('local')->response($payslip->pdf_path, $this->filename($payslip));
    }

    public function download(Request $request, Payslip $payslip)
    {
        $this->authorizeAccess($request, $payslip);

        return Storage::disk('local')->download($payslip->pdf_path, $this->filename($payslip));
    }

    private function authorizeAccess(Request $request, Payslip $payslip): void
    {
        $employee = $request->user()->employee;

        abort_unless($payslip->employee_id === $employee->id, 404);
        abort_unless($payslip->status === Payslip::STATUS_VALIDATED && $payslip->pdf_path, 404);
    }

    private function filename(Payslip $payslip): string
    {
        return 'Bulletin-'.$payslip->period->format('Y-m').'.pdf';
    }
}
