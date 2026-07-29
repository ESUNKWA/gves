<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    use HasFactory;

    public const CATEGORY_CONTRAT = 'contrat';

    public const CATEGORY_AVENANT = 'avenant';

    public const CATEGORY_ATTESTATION = 'attestation';

    public const CATEGORY_AUTRE = 'autre';

    public const FIELD_TYPE_TEXT = 'text';

    public const FIELD_TYPE_TEXTAREA = 'textarea';

    public const FIELD_TYPE_NUMBER = 'number';

    public const STEP_REQUESTER = 'requester';

    public const STEP_MANAGER = 'manager';

    public const STEP_HR = 'hr';

    public const STEP_DIRECTION = 'direction';

    public const STEP_PAYROLL = 'payroll';

    public static function stepTypes(): array
    {
        return [
            self::STEP_REQUESTER => 'Signature du demandeur',
            self::STEP_MANAGER => 'Validation du responsable hiérarchique',
            self::STEP_HR => 'Décision des Ressources Humaines',
            self::STEP_DIRECTION => 'Validation de la Direction',
            self::STEP_PAYROLL => 'Traitement Comptable / Paie',
        ];
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_CONTRAT => 'Contrat',
            self::CATEGORY_AVENANT => 'Avenant',
            self::CATEGORY_ATTESTATION => 'Attestation',
            self::CATEGORY_AUTRE => 'Autre',
        ];
    }

    public static function fieldTypes(): array
    {
        return [
            self::FIELD_TYPE_TEXT => 'Texte court',
            self::FIELD_TYPE_TEXTAREA => 'Texte long',
            self::FIELD_TYPE_NUMBER => 'Nombre',
        ];
    }

    /**
     * Merge tags available to authors when writing template content, and the
     * accessor path used to resolve each one for a given employee. Keep this
     * list in sync with GeneratedDocument::renderContent().
     */
    public static function availableVariables(): array
    {
        return [
            'Employé' => [
                '{{employe.prenom}}' => "Prénom de l'employé",
                '{{employe.nom}}' => "Nom de l'employé",
                '{{employe.nom_complet}}' => 'Nom complet',
                '{{employe.matricule}}' => 'Matricule',
                '{{employe.poste}}' => 'Intitulé du poste',
                '{{employe.departement}}' => 'Département',
                '{{employe.site}}' => 'Site',
                '{{employe.date_embauche}}' => "Date d'embauche",
                '{{employe.email}}' => 'Email personnel',
                '{{employe.telephone}}' => 'Téléphone personnel',
            ],
            'Contrat' => [
                '{{contrat.type}}' => 'Type de contrat',
                '{{contrat.poste}}' => 'Intitulé du poste (contrat)',
                '{{contrat.date_debut}}' => 'Date de début du contrat',
                '{{contrat.date_fin}}' => 'Date de fin du contrat',
                '{{contrat.fin_periode_essai}}' => "Fin de période d'essai",
                '{{contrat.salaire_base}}' => 'Salaire de base',
                '{{contrat.devise}}' => 'Devise',
                '{{contrat.heures_semaine}}' => 'Heures par semaine',
            ],
            'Entreprise' => [
                '{{entreprise.nom}}' => "Nom de l'entreprise",
                '{{entreprise.raison_sociale}}' => 'Raison sociale',
                '{{entreprise.adresse}}' => "Adresse de l'entreprise",
                '{{entreprise.telephone}}' => "Téléphone de l'entreprise",
                '{{entreprise.email}}' => "Email de l'entreprise",
                '{{entreprise.rccm}}' => "Numéro d'immatriculation (RCCM)",
                '{{entreprise.numero_fiscal}}' => 'Numéro fiscal',
                '{{entreprise.cnps}}' => "Numéro de sécurité sociale de l'entreprise",
                '{{entreprise.convention_collective}}' => 'Convention collective',
            ],
            'Autre' => [
                '{{date_jour}}' => 'Date du jour',
            ],
        ];
    }

    /**
     * This template's own custom fields, as {{demande.key}} => label — merged
     * alongside availableVariables() when picking variables for this specific
     * template's content.
     */
    public function customVariables(): array
    {
        return collect($this->fields ?? [])
            ->mapWithKeys(fn (array $field) => ["{{demande.{$field['key']}}}" => $field['label']])
            ->all();
    }

    /**
     * Validation rules for the field_values submitted alongside a request for
     * this template, e.g. ['field_values.montant' => 'required|numeric'].
     * Note: "max" means very different things for "numeric" (magnitude) vs.
     * "string" (character count) — a 2000 cap only makes sense for the latter.
     */
    public function fieldValueRules(): array
    {
        $rules = [];

        foreach ($this->fields ?? [] as $field) {
            $isNumber = ($field['type'] ?? self::FIELD_TYPE_TEXT) === self::FIELD_TYPE_NUMBER;
            $typeRule = $isNumber ? 'numeric' : 'string|max:2000';
            $presence = ($field['required'] ?? false) ? 'required' : 'nullable';

            $rules["field_values.{$field['key']}"] = "{$presence}|{$typeRule}";
        }

        return $rules;
    }

    /**
     * Whitelists a submitted field_values array down to only the keys this
     * template actually declares, dropping anything else and blank values.
     */
    public function filterFieldValues(array $fieldValues): array
    {
        return collect($this->fields ?? [])
            ->mapWithKeys(fn (array $field) => [$field['key'] => $fieldValues[$field['key']] ?? null])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    /**
     * Whether this template has an explicitly configured validation chain
     * (2+ or even a single hand-picked step). When false, a generated
     * document keeps the original behaviour: the requester alone signs it,
     * via the legacy single-signature flow (Portal\DocumentSignatureController) —
     * no GeneratedDocumentApproval rows are created for it at all.
     */
    public function hasConfiguredApprovalChain(): bool
    {
        return ! empty($this->approval_steps);
    }

    /**
     * The step chain to instantiate for a newly generated document: the
     * template's own configuration if set, otherwise an implicit single
     * "requester" step — this keeps every GeneratedDocument's lifecycle
     * uniform for callers that don't care which path produced it.
     */
    public function resolvedApprovalSteps(): array
    {
        return $this->hasConfiguredApprovalChain() ? $this->approval_steps : [self::STEP_REQUESTER];
    }

    protected $fillable = [
        'name',
        'category',
        'content',
        'fields',
        'approval_steps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'approval_steps' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }
}
