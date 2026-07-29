<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Contract extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Contract $contract) {
            if ($contract->document_path) {
                Storage::disk('local')->delete($contract->document_path);
            }
        });
    }

    public const TYPE_CDI = 'cdi';

    public const TYPE_CDD = 'cdd';

    public const TYPE_STAGE = 'stage';

    public const TYPE_INTERIM = 'interim';

    public const TYPE_PRESTATION = 'prestation';

    public const TYPE_APPRENTISSAGE = 'apprentissage';

    public static function types(): array
    {
        return [
            self::TYPE_CDI => 'CDI',
            self::TYPE_CDD => 'CDD',
            self::TYPE_STAGE => 'Stage',
            self::TYPE_INTERIM => 'Intérim',
            self::TYPE_PRESTATION => 'Prestation',
            self::TYPE_APPRENTISSAGE => 'Apprentissage',
        ];
    }

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_TERMINATED = 'terminated';

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_ENDED => 'Arrivé à terme',
            self::STATUS_TERMINATED => 'Résilié',
        ];
    }

    public const SALARY_MODE_GROSS = 'gross';

    public const SALARY_MODE_NET = 'net';

    public static function salaryModes(): array
    {
        return [
            self::SALARY_MODE_GROSS => 'Brut',
            self::SALARY_MODE_NET => 'Net',
        ];
    }

    protected $fillable = [
        'employee_id',
        'contract_type',
        'job_title',
        'start_date',
        'end_date',
        'trial_end_date',
        'base_salary',
        'salary_mode',
        'net_salary_target',
        'currency',
        'working_hours_per_week',
        'status',
        'signed_at',
        'document_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'trial_end_date' => 'date',
            'signed_at' => 'datetime',
            'base_salary' => 'decimal:2',
            'net_salary_target' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
