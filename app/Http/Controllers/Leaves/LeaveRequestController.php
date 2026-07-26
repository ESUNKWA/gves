<?php

namespace App\Http\Controllers\Leaves;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isHr = $user->can('leaves.manage');
        $managedEmployee = $user->employee;

        abort_unless($isHr || ($managedEmployee && $managedEmployee->subordinates()->exists()), 403);

        $query = LeaveRequest::with(['employee', 'leaveType']);

        if (! $isHr) {
            $query->whereIn('employee_id', $managedEmployee->subordinates()->pluck('id'));
        }

        $status = $request->string('status')->toString();

        $leaveRequests = $query
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'pending' desc")
            ->latest('start_date')
            ->paginate(15)
            ->withQueryString();

        return view('leaves.requests.index', [
            'leaveRequests' => $leaveRequests,
            'status' => $status,
            'statuses' => LeaveRequest::statuses(),
            'isHr' => $isHr,
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeDecision($request, $leaveRequest);

        abort_unless($leaveRequest->status === LeaveRequest::STATUS_PENDING, 400);

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_APPROVED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $request->input('decision_note'),
        ]);

        return back()->with('status', 'Demande approuvée.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorizeDecision($request, $leaveRequest);

        abort_unless($leaveRequest->status === LeaveRequest::STATUS_PENDING, 400);

        $data = $request->validate([
            'decision_note' => 'required|string|max:500',
        ]);

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_REJECTED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $data['decision_note'],
        ]);

        return back()->with('status', 'Demande refusée.');
    }

    private function authorizeDecision(Request $request, LeaveRequest $leaveRequest): void
    {
        $user = $request->user();

        if ($user->can('leaves.manage')) {
            return;
        }

        $managedEmployee = $user->employee;

        abort_unless($managedEmployee && $leaveRequest->employee->manager_id === $managedEmployee->id, 403);
    }
}
