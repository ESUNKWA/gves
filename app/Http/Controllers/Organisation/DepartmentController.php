<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::query()
            ->with(['site', 'parent', 'manager'])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return view('organisation.departments.index', [
            'departments' => $departments,
            'sites' => Site::orderBy('name')->get(),
            'allDepartments' => Department::orderBy('name')->get(),
            'managers' => Employee::orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        Department::create($this->validated($request));

        return redirect()->route('organisation.departments.index')->with('status', 'Département créé.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        $department->update($this->validated($request, $department));

        return redirect()->route('organisation.departments.index')->with('status', 'Département mis à jour.');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()->can('organisation.manage'), 403);

        $department->delete();

        return redirect()->route('organisation.departments.index')->with('status', 'Département supprimé.');
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($department?->id)],
            'site_id' => 'nullable|exists:sites,id',
            'parent_id' => array_filter([
                'nullable',
                'exists:departments,id',
                $department ? Rule::notIn([$department->id]) : null,
            ]),
            'manager_id' => 'nullable|exists:employees,id',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
