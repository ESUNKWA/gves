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

    public static function categories(): array
    {
        return [
            self::CATEGORY_CONTRAT => 'Contrat',
            self::CATEGORY_AVENANT => 'Avenant',
            self::CATEGORY_ATTESTATION => 'Attestation',
            self::CATEGORY_AUTRE => 'Autre',
        ];
    }

    /**
     * Merge tags available to authors when writing template content, and the
     * accessor path used to resolve each one for a given employee. Keep this
     * list in sync with GeneratedDocument::mergeFieldsFor().
     */
    public static function availableVariables(): array
    {
        return [
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
            '{{entreprise.nom}}' => "Nom de l'entreprise",
            '{{entreprise.adresse}}' => "Adresse de l'entreprise",
            '{{date_jour}}' => "Date du jour",
        ];
    }

    protected $fillable = [
        'name',
        'category',
        'content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }
}
