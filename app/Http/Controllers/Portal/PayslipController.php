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

    public function pdf(Request $request, Payslip $payslip)
    {
        $employee = $request->user()->employee;

        abort_unless($payslip->employee_id === $employee->id, 404);
        abort_unless($payslip->status === Payslip::STATUS_VALIDATED && $payslip->pdf_path, 404);

        return Storage::disk('local')->response($payslip->pdf_path, 'Bulletin-'.$payslip->period->format('Y-m').'.pdf');
    }
}
