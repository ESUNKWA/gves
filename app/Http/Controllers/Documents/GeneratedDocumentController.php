<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneratedDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $generatedDocuments = GeneratedDocument::with(['employee', 'template'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'pending' desc")
            ->latest()
            ->get();

        return view('documents.requests.index', [
            'generatedDocuments' => $generatedDocuments,
            'status' => $status,
            'statuses' => GeneratedDocument::statuses(),
        ]);
    }
}
