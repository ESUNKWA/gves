<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    public function index(): View
    {
        return view('documents.templates.index', [
            'templates' => DocumentTemplate::withCount('generatedDocuments')->orderBy('name')->get(),
            'categories' => DocumentTemplate::categories(),
            'variables' => DocumentTemplate::availableVariables(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('documents.manage'), 403);

        DocumentTemplate::create($this->validated($request));

        return redirect()->route('documents.templates.index')->with('status', 'Gabarit créé.');
    }

    public function update(Request $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        abort_unless($request->user()->can('documents.manage'), 403);

        $documentTemplate->update($this->validated($request));

        return redirect()->route('documents.templates.index')->with('status', 'Gabarit mis à jour.');
    }

    public function destroy(Request $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        abort_unless($request->user()->can('documents.manage'), 403);

        if ($documentTemplate->generatedDocuments()->exists()) {
            return redirect()->route('documents.templates.index')
                ->with('error', "Ce gabarit a déjà été utilisé pour générer des documents et ne peut pas être supprimé. Désactivez-le à la place.");
        }

        $documentTemplate->delete();

        return redirect()->route('documents.templates.index')->with('status', 'Gabarit supprimé.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:'.implode(',', array_keys(DocumentTemplate::categories())),
            'content' => 'required|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
