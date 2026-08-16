<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\Site;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $stats = $user->can('employees.view') ? [
            'activeEmployees' => Employee::where('status', Employee::STATUS_ACTIVE)->count(),
            'employees' => Employee::count(),
            'sites' => Site::count(),
            'departments' => Department::count(),
        ] : null;

        return view('dashboard', [
            'stats' => $stats,
            'myOverview' => $user->employee ? $this->employeeOverview($user->employee) : null,
        ]);
    }

    private function employeeOverview(Employee $employee): array
    {
        $schedule = $employee->effectiveWorkSchedule();

        $leaveBalances = LeaveType::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $type) => LeaveBalance::forEmployee($employee, $type, now()->year));

        return [
            'todayEntry' => $employee->timeEntries()->whereDate('date', today())->first(),
            'schedule' => $schedule,
            'leaveBalances' => $leaveBalances,
            'pendingLeaveRequests' => $employee->leaveRequests()->where('status', LeaveRequest::STATUS_PENDING)->count(),
            'pendingSignatures' => $employee->generatedDocuments()->where('status', GeneratedDocument::STATUS_PENDING)->count(),
            'latestPayslip' => $employee->payslips()->where('status', Payslip::STATUS_VALIDATED)->orderByDesc('period')->first(),
        ];
    }
}
