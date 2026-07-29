<?php

namespace App\Http\Controllers\Organisation;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeDocumentRequestController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->can('documents.manage'), 403);

        $data = $request->validate([
            'document_template_id' => 'required|exists:document_templates,id',
            'title' => 'required|string|max:255',
        ]);

        $template = DocumentTemplate::findOrFail($data['document_template_id']);

        $fieldValues = $request->validate($template->fieldValueRules())['field_values'] ?? [];

        $generatedDocument = $employee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => $data['title'],
            'content' => GeneratedDocument::renderContent($template->content, $employee, $template->filterFieldValues($fieldValues)),
            'status' => GeneratedDocument::STATUS_PENDING,
            'created_by' => $request->user()->id,
        ]);
        $generatedDocument->initializeApprovals($request->user());

        $status = $generatedDocument->fresh()->status === GeneratedDocument::STATUS_SIGNED
            ? 'Document généré et archivé.'
            : 'Document généré et prêt à être envoyé pour validation.';

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', $status)
            ->with('open_tab', 'signatures');
    }

    public function destroy(Request $request, Employee $employee, GeneratedDocument $generatedDocument): RedirectResponse
    {
        abort_unless($request->user()->can('documents.manage'), 403);
        abort_unless($generatedDocument->employee_id === $employee->id, 404);
        abort_unless($generatedDocument->status === GeneratedDocument::STATUS_PENDING, 400);

        $generatedDocument->delete();

        return redirect()->route('organisation.employees.show', $employee)
            ->with('status', 'Document annulé.')
            ->with('open_tab', 'signatures');
    }
}
