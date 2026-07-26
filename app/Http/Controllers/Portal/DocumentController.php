<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\DocumentTemplate;
use App\Models\EmployeeDocument;
use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('portal.documents.index', [
            'documents' => $employee->documents()->latest('uploaded_at')->get(),
            'categories' => EmployeeDocument::categories(),
            'pendingSignatures' => $employee->generatedDocuments()
                ->where('status', GeneratedDocument::STATUS_PENDING)
                ->latest()
                ->get(),
            'documentTemplates' => DocumentTemplate::where('is_active', true)->orderBy('name')->get(),
            'myDocumentRequests' => $employee->documentRequests()->with('template')->latest()->get(),
            'documentRequestStatuses' => DocumentRequest::statuses(),
        ]);
    }

    public function download(Request $request, EmployeeDocument $document)
    {
        $employee = $request->user()->employee;

        abort_unless($document->employee_id === $employee->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->title);
    }

    public function view(Request $request, EmployeeDocument $document)
    {
        $employee = $request->user()->employee;

        abort_unless($document->employee_id === $employee->id, 404);

        return Storage::disk('local')->response($document->file_path, $document->title);
    }
}
