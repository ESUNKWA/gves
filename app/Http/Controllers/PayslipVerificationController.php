<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Payslip;
use Illuminate\View\View;

class PayslipVerificationController extends Controller
{
    /**
     * Public, unauthenticated verification page reached by scanning a
     * payslip's QR code. Deliberately shows no salary figures — only enough
     * to confirm the document is genuine (issuer, employee, period, date).
     */
    public function __invoke(string $reference): View
    {
        $payslip = Payslip::where('reference', $reference)
            ->where('status', Payslip::STATUS_VALIDATED)
            ->with('employee')
            ->first();

        return view('verification.payslip', [
            'payslip' => $payslip,
            'company' => CompanySetting::current(),
        ]);
    }
}
