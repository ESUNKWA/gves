<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        $data = $request->validate([
            'document_template_id' => 'required|exists:document_templates,id',
            'reason' => 'nullable|string|max:1000',
        ]);

        $employee->documentRequests()->create([
            'document_template_id' => $data['document_template_id'],
            'reason' => $data['reason'] ?? null,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        return redirect()->route('portal.documents.index')->with('status', 'Demande envoyée à RH.');
    }

    public function destroy(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $employee = $request->user()->employee;

        abort_unless($documentRequest->employee_id === $employee->id, 404);
        abort_unless($documentRequest->status === DocumentRequest::STATUS_PENDING, 400);

        $documentRequest->delete();

        return redirect()->route('portal.documents.index')->with('status', 'Demande annulée.');
    }
}
