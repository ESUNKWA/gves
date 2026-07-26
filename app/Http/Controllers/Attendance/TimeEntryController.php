<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isHr = $user->can('attendance.manage');
        $managedEmployee = $user->employee;

        abort_unless($isHr || ($managedEmployee && $managedEmployee->subordinates()->exists()), 403);

        $date = $request->date('date')?->toDateString() ?? today()->toDateString();

        $query = Employee::query()->where('status', Employee::STATUS_ACTIVE);

        if (! $isHr) {
            $query->whereIn('id', $managedEmployee->subordinates()->pluck('id'));
        }

        $employees = $query
            ->with(['timeEntries' => fn ($q) => $q->whereDate('date', $date), 'workSchedule'])
            ->orderBy('first_name')
            ->get();

        return view('attendance.requests.index', [
            'employees' => $employees,
            'date' => $date,
            'isHr' => $isHr,
        ]);
    }
}
