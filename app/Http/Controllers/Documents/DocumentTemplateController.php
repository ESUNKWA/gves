<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentTemplateController extends Controller
{
    public function index(): View
    {
        return view('documents.templates.index', [
            'templates' => DocumentTemplate::withCount('generatedDocuments')->orderBy('name')->get(),
            'categories' => DocumentTemplate::categories(),
            'variables' => DocumentTemplate::availableVariables(),
            'fieldTypes' => DocumentTemplate::fieldTypes(),
            'stepTypes' => DocumentTemplate::stepTypes(),
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
                ->with('error', 'Ce gabarit a déjà été utilisé pour générer des documents et ne peut pas être supprimé. Désactivez-le à la place.');
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
            'fields' => 'nullable|array',
            'fields.*.label' => 'nullable|string|max:255',
            'fields.*.type' => 'nullable|string|in:'.implode(',', array_keys(DocumentTemplate::fieldTypes())),
            'fields.*.key' => 'nullable|string|max:100',
            'fields.*.required' => 'nullable|boolean',
            'approval_steps' => 'nullable|array',
            'approval_steps.*' => 'nullable|string|in:'.implode(',', array_keys(DocumentTemplate::stepTypes())),
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['fields'] = $this->normalizedFields($request->input('fields', []));
        $data['approval_steps'] = $this->normalizedApprovalSteps($request->input('approval_steps', []));
        $data['content'] = $this->sanitizedContent($data['content']);

        return $data;
    }

    /**
     * The content textarea is now a contenteditable rich-text editor, so what
     * arrives here is real HTML rather than plain text — including whatever a
     * pasted clipboard selection might carry. It's later rendered unescaped
     * for every viewer of the generated document (other HR staff, approvers,
     * the employee signing it), so only a small, attribute-free allowlist
     * survives: the toolbar this editor exposes (bold/italic/underline/lists)
     * never needs attributes, so stripping them entirely closes off
     * onclick=/onerror=-style injection from pasted markup.
     */
    private function sanitizedContent(string $html): string
    {
        $allowedTags = '<strong><b><em><i><u><ul><ol><li><br><div><p><span>';
        $stripped = strip_tags($html, $allowedTags);

        return preg_replace('/<(\w+)[^>]*>/', '<$1>', $stripped);
    }

    /**
     * Drop blanks and de-duplicate while preserving the chosen order — the
     * frontend already prevents adding a duplicate step type, but the server
     * is the source of truth for what actually gets persisted.
     */
    private function normalizedApprovalSteps(array $rawSteps): array
    {
        return collect($rawSteps)
            ->filter(fn ($step) => filled($step))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Drop blank rows (a field added client-side but never labeled), derive a
     * stable slug key for each field when one isn't already carried over from
     * a previous save, and de-duplicate keys so every {{demande.xxx}} tag stays
     * unambiguous.
     */
    private function normalizedFields(array $rawFields): array
    {
        $usedKeys = [];

        return collect($rawFields)
            ->filter(fn ($field) => filled($field['label'] ?? null))
            ->map(function (array $field) use (&$usedKeys) {
                $key = Str::slug(filled($field['key'] ?? null) ? $field['key'] : $field['label'], '_') ?: 'champ';

                $base = $key;
                $suffix = 2;
                while (in_array($key, $usedKeys, true)) {
                    $key = "{$base}_{$suffix}";
                    $suffix++;
                }
                $usedKeys[] = $key;

                return [
                    'key' => $key,
                    'label' => $field['label'],
                    'type' => array_key_exists($field['type'] ?? null, DocumentTemplate::fieldTypes())
                        ? $field['type']
                        : DocumentTemplate::FIELD_TYPE_TEXT,
                    'required' => (bool) ($field['required'] ?? false),
                ];
            })
            ->values()
            ->all();
    }
}
