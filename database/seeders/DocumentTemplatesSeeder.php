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
            [
                'name' => 'Avenant au contrat',
                'category' => DocumentTemplate::CATEGORY_AVENANT,
                'content' => <<<'TEXT'
                Entre {{entreprise.nom}}, sise à {{entreprise.adresse}}, ci-après désignée "l'Employeur",

                Et {{employe.nom_complet}}, matricule {{employe.matricule}}, ci-après désigné(e) "l'Employé(e)",

                Il a été convenu d'apporter l'avenant suivant au contrat de travail liant les parties depuis le {{employe.date_embauche}} :

                [Préciser ici la nature de la modification : poste, rémunération, durée, etc.]

                Toutes les autres clauses du contrat initial demeurent inchangées.

                Fait le {{date_jour}}.
                TEXT,
            ],
            [
                'name' => 'Certificat de travail',
                'category' => DocumentTemplate::CATEGORY_ATTESTATION,
                'content' => <<<'TEXT'
                Nous soussignés, {{entreprise.nom}}, certifions que {{employe.nom_complet}}, matricule {{employe.matricule}}, a été employé(e) au sein de notre entreprise du {{employe.date_embauche}} au {{employe.date_sortie}}, en qualité de {{employe.poste}} au sein du département {{employe.departement}}.

                L'intéressé(e) quitte notre entreprise libre de tout engagement.

                Ce certificat est délivré pour servir et valoir ce que de droit.

                Fait le {{date_jour}}.
                TEXT,
            ],
            [
                'name' => 'Attestation de salaire',
                'category' => DocumentTemplate::CATEGORY_ATTESTATION,
                'content' => <<<'TEXT'
                Nous soussignés, {{entreprise.nom}}, certifions que {{employe.nom_complet}}, matricule {{employe.matricule}}, est employé(e) au sein de notre entreprise depuis le {{employe.date_embauche}} en qualité de {{employe.poste}}, et perçoit à ce titre un salaire de base de {{contrat.salaire_base}} {{contrat.devise}}.

                Cette attestation est délivrée à la demande de l'intéressé(e) pour servir et valoir ce que de droit.

                Fait le {{date_jour}}.
                TEXT,
            ],
            [
                'name' => 'Attestation de présence',
                'category' => DocumentTemplate::CATEGORY_ATTESTATION,
                'content' => <<<'TEXT'
                Nous soussignés, {{entreprise.nom}}, certifions que {{employe.nom_complet}}, matricule {{employe.matricule}}, est présent(e) et actif(ve) au sein de notre entreprise à la date de ce jour, en qualité de {{employe.poste}} au sein du département {{employe.departement}}.

                Cette attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.

                Fait le {{date_jour}}.
                TEXT,
            ],
            [
                'name' => 'Attestation de congé',
                'category' => DocumentTemplate::CATEGORY_ATTESTATION,
                'content' => <<<'TEXT'
                Nous soussignés, {{entreprise.nom}}, certifions que {{employe.nom_complet}}, matricule {{employe.matricule}}, employé(e) en qualité de {{employe.poste}}, est autorisé(e) à prendre un congé conformément aux dispositions en vigueur au sein de l'entreprise.

                Cette attestation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.

                Fait le {{date_jour}}.
                TEXT,
            ],
            [
                'name' => 'Certificat de cessation de travail',
                'category' => DocumentTemplate::CATEGORY_ATTESTATION,
                'content' => <<<'TEXT'
                Nous soussignés, {{entreprise.nom}}, certifions que le contrat de travail liant notre entreprise à {{employe.nom_complet}}, matricule {{employe.matricule}}, occupant le poste de {{employe.poste}}, a pris fin le {{employe.date_sortie}}.

                Ce certificat est délivré pour servir et valoir ce que de droit.

                Fait le {{date_jour}}.
                TEXT,
            ],
            [
                'name' => "Lettre de notification d'embauche",
                'category' => DocumentTemplate::CATEGORY_AUTRE,
                'content' => <<<'TEXT'
                Objet : Confirmation d'embauche

                Madame, Monsieur {{employe.nom}},

                Nous avons le plaisir de vous confirmer votre embauche au sein de {{entreprise.nom}} en qualité de {{employe.poste}}, au sein du département {{employe.departement}}, à compter du {{employe.date_embauche}}.

                Nous vous souhaitons la bienvenue au sein de notre équipe.

                Fait le {{date_jour}}.
                TEXT,
            ],
        ];

        foreach ($templates as $template) {
            DocumentTemplate::firstOrCreate(['name' => $template['name']], $template);
        }
    }
}
