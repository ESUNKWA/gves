<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class GeneratedDocument extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SIGNED = 'signed';

    public const STATUS_CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente de signature',
            self::STATUS_SIGNED => 'Signé',
            self::STATUS_CANCELLED => 'Annulé',
        ];
    }

    protected $fillable = [
        'employee_id',
        'document_template_id',
        'employee_document_id',
        'title',
        'content',
        'status',
        'created_by',
        'signed_at',
        'signature_data',
        'signed_ip',
        'signed_user_agent',
        'document_hash',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    /**
     * Replace {{merge.tags}} in a template's raw content with an employee's
     * data plus the template's own custom field values (e.g. {{demande.montant}}
     * for a salary advance request). The template's own HTML (bold/underline/
     * lists from the rich-text editor) is trusted — authored by a documents.manage
     * user and sanitized on save, see DocumentTemplateController — so it's left
     * untouched; only the substituted values are escaped individually, since
     * those can come from employee-submitted data. Kept in sync with
     * DocumentTemplate::availableVariables().
     */
    public static function renderContent(string $rawContent, Employee $employee, array $customFieldValues = []): string
    {
        $company = CompanySetting::current();
        // The most recent contract regardless of status — a contract is
        // typically still "Brouillon" at the exact moment HR generates the
        // contract document from it, same reasoning as the "Salaire de base"
        // pre-fill on the payroll assignment screens.
        $contract = $employee->latestContract;

        $variables = [
            '{{employe.prenom}}' => e($employee->first_name),
            '{{employe.nom}}' => e($employee->last_name),
            '{{employe.nom_complet}}' => e($employee->full_name),
            '{{employe.matricule}}' => e($employee->employee_number),
            '{{employe.genre}}' => e(['male' => 'Masculin', 'female' => 'Féminin', 'other' => 'Autre'][$employee->gender] ?? ''),
            '{{employe.date_naissance}}' => e($employee->birth_date?->format('d/m/Y') ?? ''),
            '{{employe.lieu_naissance}}' => e($employee->birth_place ?? ''),
            '{{employe.nationalite}}' => e($employee->nationality ?? ''),
            '{{employe.piece_identite}}' => e($employee->national_id ?? ''),
            '{{employe.situation_familiale}}' => e($employee->marital_status ?? ''),
            '{{employe.email}}' => e($employee->personal_email ?? ''),
            '{{employe.telephone}}' => e($employee->personal_phone ?? ''),
            '{{employe.adresse}}' => e($employee->address ?? ''),
            '{{employe.ville}}' => e($employee->city ?? ''),
            '{{employe.pays}}' => e($employee->country ?? ''),
            '{{employe.compte_bancaire}}' => e($employee->bank_account_number ?? ''),
            '{{employe.numero_secu}}' => e($employee->social_security_number ?? ''),
            '{{employe.categorie}}' => e($employee->category ?? ''),
            '{{employe.qualification}}' => e($employee->qualification ?? ''),
            '{{employe.parts_fiscales}}' => e($employee->tax_shares !== null ? (string) $employee->tax_shares : ''),
            '{{employe.poste}}' => e($employee->position?->title ?? ''),
            '{{employe.departement}}' => e($employee->department?->name ?? ''),
            '{{employe.site}}' => e($employee->site?->name ?? ''),
            '{{employe.manager}}' => e($employee->manager?->full_name ?? ''),
            '{{employe.date_embauche}}' => e($employee->hire_date?->format('d/m/Y') ?? ''),
            '{{employe.date_sortie}}' => e($employee->termination_date?->format('d/m/Y') ?? ''),
            '{{employe.statut}}' => e(Employee::statuses()[$employee->status] ?? $employee->status),
            '{{contrat.type}}' => e($contract ? Contract::types()[$contract->contract_type] ?? $contract->contract_type : ''),
            '{{contrat.poste}}' => e($contract?->job_title ?? ''),
            '{{contrat.date_debut}}' => e($contract?->start_date?->format('d/m/Y') ?? ''),
            '{{contrat.date_fin}}' => e($contract?->end_date?->format('d/m/Y') ?? ''),
            '{{contrat.fin_periode_essai}}' => e($contract?->trial_end_date?->format('d/m/Y') ?? ''),
            '{{contrat.salaire_base}}' => e($contract?->base_salary !== null ? number_format($contract->base_salary, 0, ',', ' ') : ''),
            '{{contrat.devise}}' => e($contract?->currency ?? ''),
            '{{contrat.heures_semaine}}' => e($contract?->working_hours_per_week !== null ? (string) $contract->working_hours_per_week : ''),
            '{{entreprise.nom}}' => e($company->name),
            '{{entreprise.raison_sociale}}' => e($company->legal_name ?? ''),
            '{{entreprise.adresse}}' => e($company->addressLine()),
            '{{entreprise.telephone}}' => e($company->phone ?? ''),
            '{{entreprise.email}}' => e($company->email ?? ''),
            '{{entreprise.rccm}}' => e($company->registration_number ?? ''),
            '{{entreprise.numero_fiscal}}' => e($company->tax_id ?? ''),
            '{{entreprise.cnps}}' => e($company->social_security_number ?? ''),
            '{{entreprise.convention_collective}}' => e($company->collective_agreement ?? ''),
            '{{date_jour}}' => now()->format('d/m/Y'),
        ];

        foreach ($customFieldValues as $key => $value) {
            $variables["{{demande.{$key}}}"] = e((string) $value);
        }

        $merged = strtr($rawContent, $variables);

        // Templates written before the rich-text editor stored plain text with
        // raw newlines; content produced by the editor already carries its own
        // <br>/<div> line breaks, so only auto-convert when no markup is present.
        return str_contains($merged, '<') ? $merged : nl2br($merged);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employeeDocument(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocument::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(GeneratedDocumentApproval::class)->orderBy('step_order');
    }

    /**
     * The step currently awaiting a decision — approvals are always resolved
     * strictly in step_order, so the earliest pending row is always the one
     * that's actually reachable (a rejection cancels the whole document, so
     * there's never a pending step behind an unresolved earlier one).
     */
    public function currentApproval(): ?GeneratedDocumentApproval
    {
        return $this->approvals->firstWhere('status', GeneratedDocumentApproval::STATUS_PENDING);
    }

    /**
     * Creates the approval chain rows from the template's configuration, if
     * it has one. Templates without an explicit chain create no rows at all —
     * such a document keeps the original single-signature-by-the-requester
     * behaviour (Portal\DocumentSignatureController), completely untouched.
     *
     * $decidedBy is the user performing the action that produced this document
     * (approving the employee's request, or generating it directly). When the
     * chain's very first step is STEP_HR, that action already *is* the HR
     * decision — auto-approving it here avoids making the same HR user click
     * "Approuver" a second time in /validations for what is, in substance,
     * a single decision.
     */
    public function initializeApprovals(?User $decidedBy = null): void
    {
        if (! $this->template?->hasConfiguredApprovalChain()) {
            return;
        }

        foreach ($this->template->resolvedApprovalSteps() as $index => $stepType) {
            $this->approvals()->create([
                'step_type' => $stepType,
                'step_order' => $index,
                'status' => GeneratedDocumentApproval::STATUS_PENDING,
            ]);
        }

        if ($decidedBy) {
            $this->autoApproveLeadingHrStep($decidedBy);
        }
    }

    private function autoApproveLeadingHrStep(User $decidedBy): void
    {
        $this->unsetRelation('approvals');
        $current = $this->currentApproval();

        if (! $current || $current->step_type !== DocumentTemplate::STEP_HR) {
            return;
        }

        $current->update([
            'status' => GeneratedDocumentApproval::STATUS_APPROVED,
            'decided_by' => $decidedBy->id,
            'decided_at' => now(),
        ]);

        $this->unsetRelation('approvals');

        if (! $this->currentApproval()) {
            $this->finalize();
        }
    }

    public function employeeDocumentCategory(): string
    {
        return match ($this->template?->category) {
            DocumentTemplate::CATEGORY_CONTRAT, DocumentTemplate::CATEGORY_AVENANT => EmployeeDocument::CATEGORY_CONTRAT,
            DocumentTemplate::CATEGORY_ATTESTATION => EmployeeDocument::CATEGORY_ATTESTATION,
            default => EmployeeDocument::CATEGORY_AUTRE,
        };
    }

    /**
     * Every configured step has been approved (or there was no chain to begin
     * with — see initializeApprovals()): render the final PDF, archive it as
     * an EmployeeDocument, and mark the workflow complete. Used both by
     * ApprovalController (last step approved) and by autoApproveLeadingHrStep()
     * (single-step "hr"-only chains resolved in one click).
     */
    public function finalize(): void
    {
        $employee = $this->employee;
        $signedAt = now();
        $hash = hash('sha256', $this->content);

        $pdf = Pdf::loadView('documents.pdf', [
            'generatedDocument' => $this,
            'employee' => $employee,
            'company' => CompanySetting::current(),
            'hash' => $hash,
            'signedAt' => $signedAt,
            'signatureData' => null,
            'signedIp' => null,
        ]);

        $path = 'employee-documents/'.$employee->id.'/'.uniqid('signed_').'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        $lastApproval = $this->approvals()->orderByDesc('step_order')->first();

        $employeeDocument = $employee->documents()->create([
            'category' => $this->employeeDocumentCategory(),
            'title' => $this->title,
            'file_path' => $path,
            'uploaded_by' => $lastApproval?->decided_by,
            'uploaded_at' => $signedAt,
        ]);

        $this->update([
            'status' => self::STATUS_SIGNED,
            'employee_document_id' => $employeeDocument->id,
            'signed_at' => $signedAt,
            'document_hash' => $hash,
        ]);
    }
}
