<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\GeneratedDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $documentRequests = DocumentRequest::with(['employee', 'template'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('documents.document-requests.index', [
            'documentRequests' => $documentRequests,
            'status' => $status,
            'statuses' => DocumentRequest::statuses(),
        ]);
    }

    public function approve(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        abort_unless($request->user()->can('documents.manage'), 403);
        abort_unless($documentRequest->status === DocumentRequest::STATUS_PENDING, 400);
        abort_unless($documentRequest->document_template_id, 400, 'Aucun gabarit associé à cette demande.');

        $employee = $documentRequest->employee;
        $template = $documentRequest->template;

        $generatedDocument = $employee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => $template->name.' — '.$employee->full_name,
            'content' => GeneratedDocument::renderContent($template->content, $employee),
            'status' => GeneratedDocument::STATUS_PENDING,
            'created_by' => $request->user()->id,
        ]);

        $documentRequest->update([
            'status' => DocumentRequest::STATUS_FULFILLED,
            'generated_document_id' => $generatedDocument->id,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return back()->with('status', 'Document généré et envoyé à l\'employé pour signature.');
    }

    public function reject(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        abort_unless($request->user()->can('documents.manage'), 403);
        abort_unless($documentRequest->status === DocumentRequest::STATUS_PENDING, 400);

        $data = $request->validate([
            'decision_note' => 'required|string|max:500',
        ]);

        $documentRequest->update([
            'status' => DocumentRequest::STATUS_REJECTED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $data['decision_note'],
        ]);

        return back()->with('status', 'Demande refusée.');
    }
}
