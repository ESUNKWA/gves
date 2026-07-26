<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeLeaveRequestController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($this->overlapsExistingRequest($employee, $data['start_date'], $data['end_date'])) {
            return back()
                ->withErrors(['start_date' => "Cet employé a déjà une demande de congé sur cette période."])
                ->withInput()
                ->with('open_tab', 'leaves');
        }

        $employee->leaveRequests()->create([
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => LeaveRequest::calculateDaysCount($data['start_date'], $data['end_date']),
            'reason' => $data['reason'] ?? null,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Demande de congé créée.')
            ->with('open_tab', 'leaves');
    }

    public function destroy(Request $request, Employee $employee, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($leaveRequest->employee_id === $employee->id, 404);
        abort_unless($leaveRequest->status === LeaveRequest::STATUS_PENDING, 400);

        $leaveRequest->delete();

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Demande de congé supprimée.')
            ->with('open_tab', 'leaves');
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
