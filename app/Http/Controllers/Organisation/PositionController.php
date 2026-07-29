<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(): View
    {
        $positions = Position::query()
            ->with('department')
            ->withCount('employees')
            ->orderBy('title')
            ->get();

        return view('organisation.positions.index', [
            'positions' => $positions,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        Position::create($this->validated($request));

        return redirect()->route('organisation.positions.index')->with('status', 'Poste créé.');
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        $position->update($this->validated($request, $position));

        return redirect()->route('organisation.positions.index')->with('status', 'Poste mis à jour.');
    }

    public function destroy(Request $request, Position $position): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        $position->delete();

        return redirect()->route('organisation.positions.index')->with('status', 'Poste supprimé.');
    }

    private function validated(Request $request, ?Position $position = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('positions', 'code')->ignore($position?->id)],
            'department_id' => 'nullable|exists:departments,id',
            'description' => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
