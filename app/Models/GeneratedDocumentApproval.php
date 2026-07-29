<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocumentApproval extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_APPROVED => 'Approuvée',
            self::STATUS_REJECTED => 'Refusée',
        ];
    }

    protected $fillable = [
        'generated_document_id',
        'step_type',
        'step_order',
        'status',
        'decided_by',
        'decided_at',
        'signature_data',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function generatedDocument(): BelongsTo
    {
        return $this->belongsTo(GeneratedDocument::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function label(): string
    {
        return DocumentTemplate::stepTypes()[$this->step_type] ?? $this->step_type;
    }

    /**
     * Central authorization rule for every step type: who is allowed to act
     * on this specific approval. Kept in one place so the queue listing, the
     * consult screen, and the approve/reject actions can never disagree.
     */
    public function canBeActedOnBy(User $user): bool
    {
        $employee = $this->generatedDocument->employee;

        return match ($this->step_type) {
            DocumentTemplate::STEP_REQUESTER => $user->employee?->id === $employee->id,
            DocumentTemplate::STEP_MANAGER => $employee->manager_id !== null && $user->employee?->id === $employee->manager_id,
            DocumentTemplate::STEP_HR => $user->can('documents.manage'),
            DocumentTemplate::STEP_DIRECTION => $user->can('direction.manage'),
            DocumentTemplate::STEP_PAYROLL => $user->can('payroll.manage'),
            default => false,
        };
    }
}
