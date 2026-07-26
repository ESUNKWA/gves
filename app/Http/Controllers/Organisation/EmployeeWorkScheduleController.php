<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeWorkScheduleController extends Controller
{
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $rules = [];

        foreach (WorkSchedule::DAYS as $day) {
            $rules["{$day}_start"] = "nullable|date_format:H:i|required_with:{$day}_end";
            $rules["{$day}_end"] = "nullable|date_format:H:i|required_with:{$day}_start|after:{$day}_start";
        }

        $data = $request->validate($rules);

        WorkSchedule::updateOrCreate(['employee_id' => $employee->id], $data);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Horaire de travail mis à jour.')
            ->with('open_tab', 'attendance');
    }
}
