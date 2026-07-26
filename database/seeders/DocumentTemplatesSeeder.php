<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

class DocumentTemplatesSeeder extends Seeder
{
    /**
     * Seed a couple of starter templates. These are editable examples, not
     * legally vetted contract wording — the client's own HR/legal team should
     * review and adjust them under Documents > Gabarits before real use.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Attestation de travail',
                'category' => DocumentTemplate::CATEGORY_ATTESTATION,
                'content' => <<<'TEXT'
                Nous soussignés, {{entreprise.nom}}, certifions que {{employe.nom_complet}}, matricule {{employe.matricule}}, est employé(e) au sein de notre entreprise depuis le {{employe.date_embauche}} en qualité de {{employe.poste}} au sein du département {{employe.departement}}.

                Cette attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.

                Fait le {{date_jour}}.
                TEXT,
            ],
            [
                'name' => 'Contrat de travail (générique)',
                'category' => DocumentTemplate::CATEGORY_CONTRAT,
                'content' => <<<'TEXT'
                Entre {{entreprise.nom}}, sise à {{entreprise.adresse}}, ci-après désignée "l'Employeur",

                Et {{employe.nom_complet}}, ci-après désigné(e) "l'Employé(e)",

                Il a été convenu ce qui suit :

                L'Employé(e), matricule {{employe.matricule}}, est engagé(e) à compter du {{employe.date_embauche}} en qualité de {{employe.poste}}, au sein du département {{employe.departement}} ({{employe.site}}).

                Le présent contrat est régi par les dispositions légales en vigueur.

                Fait le {{date_jour}}.
                TEXT,
            ],
        ];

        foreach ($templates as $template) {
            DocumentTemplate::firstOrCreate(['name' => $template['name']], $template);
        }
    }
}
