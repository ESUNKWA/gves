<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeTimeEntryController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i|after:clock_in',
            'note' => 'nullable|string|max:500',
        ]);

        $attributes = [
            'clock_in' => $data['clock_in'] ? "{$data['date']} {$data['clock_in']}" : null,
            'clock_out' => $data['clock_out'] ? "{$data['date']} {$data['clock_out']}" : null,
            'note' => $data['note'] ?? null,
            'source' => TimeEntry::SOURCE_MANUAL,
            'corrected_by' => $request->user()->id,
        ];

        $entry = $employee->timeEntries()->whereDate('date', $data['date'])->first();

        if ($entry) {
            $entry->update($attributes);
        } else {
            $employee->timeEntries()->create(['date' => $data['date']] + $attributes);
        }

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Pointage enregistré.')
            ->with('open_tab', 'attendance');
    }

    public function destroy(Request $request, Employee $employee, TimeEntry $timeEntry): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($timeEntry->employee_id === $employee->id, 404);

        $timeEntry->delete();

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Pointage supprimé.')
            ->with('open_tab', 'attendance');
    }
}
