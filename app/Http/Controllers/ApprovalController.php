<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Models\GeneratedDocumentApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The unified "things awaiting my decision" inbox for the configurable
 * multi-step document approval chain (Signature du demandeur, Validation du
 * responsable hiérarchique, Décision RH, Validation de la Direction,
 * Traitement Comptable/Paie). A single controller handles every step type —
 * who's allowed to act is entirely decided by GeneratedDocumentApproval::
 * canBeActedOnBy(), so this stays generic rather than one module per role.
 *
 * Documents whose template has no configured approval_steps never produce
 * any GeneratedDocumentApproval rows (see GeneratedDocument::initializeApprovals())
 * and so never appear here — those keep using the original, untouched
 * single-signature flow in Portal\DocumentSignatureController.
 */
class ApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $approvals = $this->pendingApprovalsFor($request->user());

        return view('approvals.index', ['approvals' => $approvals]);
    }

    public function show(Request $request, GeneratedDocumentApproval $approval): View
    {
        $this->authorizeCurrentStep($request, $approval);

        $approval->load(['generatedDocument.employee', 'generatedDocument.approvals.decidedBy']);

        return view('approvals.show', ['approval' => $approval]);
    }

    public function approve(Request $request, GeneratedDocumentApproval $approval): RedirectResponse
    {
        $this->authorizeCurrentStep($request, $approval);

        $data = $request->validate([
            'signature_data' => 'nullable|string|starts_with:data:image/png;base64,',
            'note' => 'nullable|string|max:1000',
        ]);

        $approval->update([
            'status' => GeneratedDocumentApproval::STATUS_APPROVED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'signature_data' => $data['signature_data'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        $generatedDocument = $approval->generatedDocument->fresh('approvals');

        if (! $generatedDocument->currentApproval()) {
            $generatedDocument->finalize();
        }

        return redirect()->route('approvals.index')->with('status', 'Étape validée.');
    }

    public function reject(Request $request, GeneratedDocumentApproval $approval): RedirectResponse
    {
        $this->authorizeCurrentStep($request, $approval);

        $data = $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $approval->update([
            'status' => GeneratedDocumentApproval::STATUS_REJECTED,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'note' => $data['note'],
        ]);

        $approval->generatedDocument->update(['status' => GeneratedDocument::STATUS_CANCELLED]);

        return redirect()->route('approvals.index')->with('status', 'Demande refusée.');
    }

    private function authorizeCurrentStep(Request $request, GeneratedDocumentApproval $approval): void
    {
        abort_unless($approval->canBeActedOnBy($request->user()), 403);
        abort_unless($approval->status === GeneratedDocumentApproval::STATUS_PENDING, 400);
        abort_unless(
            $approval->id === $approval->generatedDocument->currentApproval()?->id,
            400,
            "Ce n'est pas encore le tour de cette étape."
        );
    }

    private function pendingApprovalsFor($user)
    {
        return GeneratedDocument::query()
            ->where('status', GeneratedDocument::STATUS_PENDING)
            ->whereHas('approvals', fn ($query) => $query->where('status', GeneratedDocumentApproval::STATUS_PENDING))
            ->with(['employee', 'template', 'approvals'])
            ->get()
            ->map(fn (GeneratedDocument $document) => $document->currentApproval())
            ->filter()
            ->filter(fn (GeneratedDocumentApproval $approval) => $approval->canBeActedOnBy($user))
            ->values();
    }
}
