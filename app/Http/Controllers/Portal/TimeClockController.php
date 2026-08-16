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
        $location = $this->validatedLocation($request);

        $entry = $employee->timeEntries()->whereDate('date', $today)->first()
            ?? new TimeEntry(['employee_id' => $employee->id, 'date' => $today]);

        abort_if($entry->exists && $entry->clock_in, 400, "Vous avez déjà pointé votre arrivée aujourd'hui.");

        $entry->clock_in = now();
        $entry->clock_in_ip = $request->ip();
        $entry->clock_in_latitude = $location['latitude'];
        $entry->clock_in_longitude = $location['longitude'];
        $entry->source = TimeEntry::SOURCE_SELF;
        $entry->save();

        return redirect()->route('portal.time-clock.index')->with('status', 'Arrivée enregistrée à '.now()->format('H:i').'.');
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        $today = today()->toDateString();
        $location = $this->validatedLocation($request);

        $entry = $employee->timeEntries()->whereDate('date', $today)->first();

        abort_if(! $entry || ! $entry->clock_in, 400, "Vous devez d'abord pointer votre arrivée.");
        abort_if($entry->clock_out, 400, "Vous avez déjà pointé votre départ aujourd'hui.");

        $entry->update([
            'clock_out' => now(),
            'clock_out_ip' => $request->ip(),
            'clock_out_latitude' => $location['latitude'],
            'clock_out_longitude' => $location['longitude'],
        ]);

        return redirect()->route('portal.time-clock.index')->with('status', 'Départ enregistré à '.now()->format('H:i').'.');
    }

    /**
     * Geolocation is best-effort: the browser prompt may be denied, time out,
     * or simply not fire in time (see the x-data handler in
     * resources/views/portal/time-clock/index.blade.php) — an audit trail,
     * not a requirement, so a clock-in/out is never blocked on having
     * coordinates. The IP is always recorded server-side regardless.
     */
    private function validatedLocation(Request $request): array
    {
        return array_merge(['latitude' => null, 'longitude' => null], $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]));
    }
}
