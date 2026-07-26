<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeDocumentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:'.implode(',', array_keys(EmployeeDocument::categories())),
            'file' => 'required|file|max:10240',
        ]);

        $path = $request->file('file')->store('employee-documents/'.$employee->id, 'local');

        $employee->documents()->create([
            'category' => $data['category'],
            'title' => $data['title'],
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Document ajouté.')
            ->with('open_tab', 'documents');
    }

    public function destroy(Request $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        abort_unless($request->user()->can('employees.manage'), 403);
        abort_unless($document->employee_id === $employee->id, 404);

        $document->delete();

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Document supprimé.')
            ->with('open_tab', 'documents');
    }
}
