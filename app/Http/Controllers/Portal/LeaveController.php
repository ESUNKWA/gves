<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        $year = now()->year;

        $activeLeaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();
        $leaveBalances = $activeLeaveTypes->map(fn (LeaveType $type) => LeaveBalance::forEmployee($employee, $type, $year));

        return view('portal.leaves.index', [
            'leaveBalances' => $leaveBalances,
            'leaveTypes' => $activeLeaveTypes,
            'leaveRequests' => $employee->leaveRequests()->with('leaveType')->latest('start_date')->get(),
            'leaveStatuses' => LeaveRequest::statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($this->overlapsExistingRequest($employee, $data['start_date'], $data['end_date'])) {
            return back()
                ->withErrors(['start_date' => 'Vous avez déjà une demande de congé sur cette période.'])
                ->withInput();
        }

        $employee->leaveRequests()->create([
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => LeaveRequest::calculateDaysCount($data['start_date'], $data['end_date']),
            'reason' => $data['reason'] ?? null,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        return redirect()->route('portal.leaves.index')->with('status', 'Demande de congé envoyée.');
    }

    public function destroy(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $employee = $request->user()->employee;

        abort_unless($leaveRequest->employee_id === $employee->id, 403);
        abort_unless($leaveRequest->status === LeaveRequest::STATUS_PENDING, 400);

        $leaveRequest->delete();

        return redirect()->route('portal.leaves.index')->with('status', 'Demande annulée.');
    }

    private function overlapsExistingRequest(Employee $employee, string $start, string $end): bool
    {
        return $employee->leaveRequests()
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }
}
