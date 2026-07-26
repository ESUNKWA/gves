<?php

namespace App\Http\Controllers\Portal;

use App\Models\CompanySetting;
use App\Models\DocumentTemplate;
use App\Models\EmployeeDocument;
use App\Models\GeneratedDocument;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentSignatureController extends Controller
{
    public function show(Request $request, GeneratedDocument $generatedDocument): View
    {
        $employee = $request->user()->employee;

        abort_unless($generatedDocument->employee_id === $employee->id, 404);
        abort_unless($generatedDocument->status === GeneratedDocument::STATUS_PENDING, 400);

        return view('portal.documents.sign', [
            'generatedDocument' => $generatedDocument,
            'company' => CompanySetting::current(),
        ]);
    }

    public function sign(Request $request, GeneratedDocument $generatedDocument): RedirectResponse
    {
        $employee = $request->user()->employee;

        abort_unless($generatedDocument->employee_id === $employee->id, 404);
        abort_unless($generatedDocument->status === GeneratedDocument::STATUS_PENDING, 400);

        $data = $request->validate([
            'signature_data' => 'required|string|starts_with:data:image/png;base64,',
            'consent' => 'accepted',
        ]);

        $signedAt = now();
        $hash = hash('sha256', $generatedDocument->content);

        $pdf = Pdf::loadView('documents.pdf', [
            'generatedDocument' => $generatedDocument,
            'employee' => $employee,
            'company' => CompanySetting::current(),
            'signatureData' => $data['signature_data'],
            'signedAt' => $signedAt,
            'signedIp' => $request->ip(),
            'hash' => $hash,
        ]);

        $path = 'employee-documents/'.$employee->id.'/'.uniqid('signed_').'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        $employeeDocument = $employee->documents()->create([
            'category' => $this->employeeDocumentCategory($generatedDocument),
            'title' => $generatedDocument->title,
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => $signedAt,
        ]);

        $generatedDocument->update([
            'status' => GeneratedDocument::STATUS_SIGNED,
            'employee_document_id' => $employeeDocument->id,
            'signed_at' => $signedAt,
            'signature_data' => $data['signature_data'],
            'signed_ip' => $request->ip(),
            'signed_user_agent' => (string) $request->userAgent(),
            'document_hash' => $hash,
        ]);

        return redirect()->route('portal.documents.index')->with('status', 'Document signé et archivé dans vos documents.');
    }

    private function employeeDocumentCategory(GeneratedDocument $generatedDocument): string
    {
        return match ($generatedDocument->template?->category) {
            DocumentTemplate::CATEGORY_CONTRAT, DocumentTemplate::CATEGORY_AVENANT => EmployeeDocument::CATEGORY_CONTRAT,
            DocumentTemplate::CATEGORY_ATTESTATION => EmployeeDocument::CATEGORY_ATTESTATION,
            default => EmployeeDocument::CATEGORY_AUTRE,
        };
    }
}
