<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (EmployeeDocument $document) {
            Storage::disk('local')->delete($document->file_path);
        });
    }

    public const CATEGORY_CONTRAT = 'contrat';

    public const CATEGORY_DIPLOME = 'diplome';

    public const CATEGORY_PIECE_IDENTITE = 'piece_identite';

    public const CATEGORY_CV = 'cv';

    public const CATEGORY_ATTESTATION = 'attestation';

    public const CATEGORY_AUTRE = 'autre';

    public static function categories(): array
    {
        return [
            self::CATEGORY_CONTRAT => 'Contrat',
            self::CATEGORY_DIPLOME => 'Diplôme',
            self::CATEGORY_PIECE_IDENTITE => "Pièce d'identité",
            self::CATEGORY_CV => 'CV',
            self::CATEGORY_ATTESTATION => 'Attestation',
            self::CATEGORY_AUTRE => 'Autre',
        ];
    }

    protected $fillable = [
        'employee_id',
        'category',
        'title',
        'file_path',
        'uploaded_by',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
