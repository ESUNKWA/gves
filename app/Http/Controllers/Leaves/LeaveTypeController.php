<?php

namespace App\Http\Controllers\Leaves;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        return view('leaves.types.index', [
            'leaveTypes' => LeaveType::withCount(['leaveRequests', 'leaveBalances'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('leaves.manage'), 403);

        LeaveType::create($this->validated($request));

        return redirect()->route('leaves.types.index')->with('status', 'Type de congé créé.');
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        abort_unless($request->user()->can('leaves.manage'), 403);

        $leaveType->update($this->validated($request, $leaveType));

        return redirect()->route('leaves.types.index')->with('status', 'Type de congé mis à jour.');
    }

    public function destroy(Request $request, LeaveType $leaveType): RedirectResponse
    {
        abort_unless($request->user()->can('leaves.manage'), 403);

        if ($leaveType->leaveRequests()->exists() || $leaveType->leaveBalances()->exists()) {
            return redirect()->route('leaves.types.index')
                ->with('error', "Ce type de congé a déjà été utilisé et ne peut pas être supprimé. Désactivez-le à la place.");
        }

        $leaveType->delete();

        return redirect()->route('leaves.types.index')->with('status', 'Type de congé supprimé.');
    }

    private function validated(Request $request, ?LeaveType $leaveType = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:20', Rule::unique('leave_types', 'code')->ignore($leaveType?->id)],
            'accrual_days_per_month' => 'nullable|numeric|min:0|max:31',
            'max_carry_over_days' => 'nullable|integer|min:0',
        ]);

        $data['is_paid'] = $request->boolean('is_paid');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
