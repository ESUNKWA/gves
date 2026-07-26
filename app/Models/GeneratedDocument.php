<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * data, then escape the result as a single pass so both the template
     * author's text and the substituted values are safe to output as HTML.
     * Kept in sync with DocumentTemplate::availableVariables().
     */
    public static function renderContent(string $rawContent, Employee $employee): string
    {
        $company = CompanySetting::current();

        $variables = [
            '{{employe.prenom}}' => $employee->first_name,
            '{{employe.nom}}' => $employee->last_name,
            '{{employe.nom_complet}}' => $employee->full_name,
            '{{employe.matricule}}' => $employee->employee_number,
            '{{employe.poste}}' => $employee->position?->title ?? '',
            '{{employe.departement}}' => $employee->department?->name ?? '',
            '{{employe.site}}' => $employee->site?->name ?? '',
            '{{employe.date_embauche}}' => $employee->hire_date?->format('d/m/Y') ?? '',
            '{{employe.email}}' => $employee->personal_email ?? '',
            '{{employe.telephone}}' => $employee->personal_phone ?? '',
            '{{entreprise.nom}}' => $company->name,
            '{{entreprise.adresse}}' => trim("{$company->address} {$company->city} {$company->country}"),
            '{{date_jour}}' => now()->format('d/m/Y'),
        ];

        $merged = strtr($rawContent, $variables);

        return nl2br(e($merged));
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
}
