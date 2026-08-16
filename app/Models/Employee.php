<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_LEAVE = 'on_leave';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_TERMINATED = 'terminated';

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_ON_LEAVE => 'En congé',
            self::STATUS_SUSPENDED => 'Suspendu',
            self::STATUS_TERMINATED => 'Sorti',
        ];
    }

    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'birth_place',
        'nationality',
        'national_id',
        'personal_email',
        'personal_phone',
        'address',
        'city',
        'country',
        'bank_account_number',
        'social_security_number',
        'category',
        'qualification',
        'tax_shares',
        'marital_status',
        'site_id',
        'department_id',
        'position_id',
        'manager_id',
        'hire_date',
        'status',
        'termination_date',
        'photo_path',
    ];

    protected $appends = [
        'full_name',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'is_anonymized' => 'boolean',
            'anonymized_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * The bank account number with all but the last 4 characters masked, for
     * display on documents like payslips.
     */
    public function maskedBankAccount(): ?string
    {
        if (! $this->bank_account_number) {
            return null;
        }

        $clean = preg_replace('/\s+/', '', $this->bank_account_number);
        $last4 = substr($clean, -4);

        return str_repeat('•', 4).' '.str_repeat('•', 4).' '.str_repeat('•', 4).' '.$last4;
    }

    /**
     * Seniority as of a given date, e.g. "1 an(s) et 9 mois". Null if there's
     * no hire date or it's in the future relative to $asOf.
     */
    public function seniorityLabel(Carbon $asOf): ?string
    {
        if (! $this->hire_date || $this->hire_date->greaterThan($asOf)) {
            return null;
        }

        // Plain integer year/month arithmetic — Carbon's diffInMonths() can
        // return a fractional value depending on version, which would
        // silently corrupt this otherwise-whole-number calculation.
        $totalMonths = ($asOf->year - $this->hire_date->year) * 12 + ($asOf->month - $this->hire_date->month);

        if ($asOf->day < $this->hire_date->day) {
            $totalMonths--;
        }

        $totalMonths = max(0, $totalMonths);
        $years = intdiv($totalMonths, 12);
        $months = $totalMonths % 12;

        return "{$years} an(s) et {$months} mois";
    }

    public static function nextEmployeeNumber(): string
    {
        $last = static::withTrashed()->max('id') ?? 0;

        return 'EMP-'.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * RGPD: erase personal data while keeping records required for legal/payroll history.
     */
    public function anonymize(): void
    {
        $this->documents->each->delete();

        // The signed HTML content of a generated document has the employee's
        // real name and personal data baked in as plain text (unlike Contract,
        // whose PII lives entirely in columns already blanked above), so it
        // must be redacted separately even though the row itself is kept for
        // the legal signature audit trail (status, signed_at, document_hash).
        $this->generatedDocuments()->update([
            'title' => 'Document anonymisé',
            'content' => 'Le contenu de ce document a été anonymisé conformément au RGPD.',
        ]);

        $this->forceFill([
            'first_name' => 'Anonymisé',
            'last_name' => 'EMP-'.$this->id,
            'gender' => null,
            'birth_date' => null,
            'birth_place' => null,
            'nationality' => null,
            'national_id' => null,
            'personal_email' => null,
            'personal_phone' => null,
            'address' => null,
            'city' => null,
            'country' => null,
            'marital_status' => null,
            'photo_path' => null,
            'is_anonymized' => true,
            'anonymized_at' => now(),
        ])->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract(): HasOne
    {
        return $this->hasOne(Contract::class)->where('status', 'active')->latestOfMany('start_date');
    }

    /**
     * Most recent contract regardless of status — used to pre-fill "Salaire
     * de base" when assigning payroll components, since a freshly entered
     * contract commonly still sits in "Brouillon" (draft) rather than
     * "Actif" at that point, which activeContract() would miss entirely.
     */
    public function latestContract(): HasOne
    {
        return $this->hasOne(Contract::class)->latestOfMany('start_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function workSchedule(): HasOne
    {
        return $this->hasOne(WorkSchedule::class);
    }

    /**
     * This employee's own schedule if one was set, otherwise the company-wide
     * default (WorkSchedule::default()) — what late/overtime calculations
     * and payslip contractual-hours should always use, rather than the raw
     * workSchedule() relation which is null for anyone without an override.
     */
    public function effectiveWorkSchedule(): WorkSchedule
    {
        return $this->workSchedule ?? WorkSchedule::default();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function payComponents(): HasMany
    {
        return $this->hasMany(EmployeePayComponent::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
