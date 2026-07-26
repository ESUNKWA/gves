<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        return view('administration.holidays.index', [
            'holidays' => Holiday::orderBy('date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Holiday::create($this->validated($request));

        return redirect()->route('administration.holidays.index')->with('status', 'Jour férié ajouté.');
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update($this->validated($request, $holiday));

        return redirect()->route('administration.holidays.index')->with('status', 'Jour férié mis à jour.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return redirect()->route('administration.holidays.index')->with('status', 'Jour férié supprimé.');
    }

    private function validated(Request $request, ?Holiday $holiday = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'date' => ['required', 'date', Rule::unique('holidays', 'date')->ignore($holiday?->id)],
        ]);
    }
}
