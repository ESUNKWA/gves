<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DefaultWorkScheduleController extends Controller
{
    /**
     * The company-wide schedule (WorkSchedule::default(), employee_id null)
     * applied to every employee that has no schedule of their own — see
     * Employee::effectiveWorkSchedule(). Per-employee overrides still go
     * through EmployeeWorkScheduleController, unaffected by this.
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('attendance.manage'), 403);

        $rules = [];

        foreach (WorkSchedule::DAYS as $day) {
            $rules["{$day}_start"] = "nullable|date_format:H:i|required_with:{$day}_end";
            $rules["{$day}_end"] = "nullable|date_format:H:i|required_with:{$day}_start|after:{$day}_start";
        }

        $data = $request->validate($rules);

        WorkSchedule::default()->update($data);

        return redirect()->route('attendance.requests.index')
            ->with('status', 'Horaire par défaut mis à jour.');
    }
}
