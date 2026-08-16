<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeClockController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;
        $schedule = $employee->effectiveWorkSchedule();

        $today = today()->toDateString();
        $todayEntry = $employee->timeEntries()->whereDate('date', $today)->first();

        $recentEntries = $employee->timeEntries()
            ->whereDate('date', '>=', now()->subDays(14)->toDateString())
            ->orderByDesc('date')
            ->get();

        $monthEntries = $employee->timeEntries()
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->get();

        return view('portal.time-clock.index', [
            'todayEntry' => $todayEntry,
            'recentEntries' => $recentEntries,
            'schedule' => $schedule,
            'monthWorkedMinutes' => $monthEntries->sum(fn (TimeEntry $entry) => $entry->workedMinutes() ?? 0),
            'monthLateMinutes' => $monthEntries->sum(fn (TimeEntry $entry) => $entry->lateMinutes($schedule)),
            'monthOvertimeMinutes' => $monthEntries->sum(fn (TimeEntry $entry) => $entry->overtimeMinutes($schedule)),
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        $today = today()->toDateString();

        $entry = $employee->timeEntries()->whereDate('date', $today)->first()
            ?? new TimeEntry(['employee_id' => $employee->id, 'date' => $today]);

        abort_if($entry->exists && $entry->clock_in, 400, "Vous avez déjà pointé votre arrivée aujourd'hui.");

        $entry->clock_in = now();
        $entry->source = TimeEntry::SOURCE_SELF;
        $entry->save();

        return redirect()->route('portal.time-clock.index')->with('status', 'Arrivée enregistrée à '.now()->format('H:i').'.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        $today = today()->toDateString();

        $entry = $employee->timeEntries()->whereDate('date', $today)->first();

        abort_if(! $entry || ! $entry->clock_in, 400, "Vous devez d'abord pointer votre arrivée.");
        abort_if($entry->clock_out, 400, "Vous avez déjà pointé votre départ aujourd'hui.");

        $entry->update(['clock_out' => now()]);

        return redirect()->route('portal.time-clock.index')->with('status', 'Départ enregistré à '.now()->format('H:i').'.');
    }
}
