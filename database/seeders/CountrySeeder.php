<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Seed a starter list of countries. This is an editable default, not a
     * fixed list — add/remove entries under Administration > Pays.
     */
    public function run(): void
    {
        $countries = [
            "Côte d'Ivoire",
            'Sénégal',
            'Mali',
            'Burkina Faso',
            'Bénin',
            'Togo',
            'Niger',
            'Guinée',
            'Ghana',
            'Nigeria',
            'Cameroun',
            'Gabon',
            'Congo',
            'République Démocratique du Congo',
            'Maroc',
            'Tunisie',
            'Algérie',
            'France',
        ];

        foreach ($countries as $name) {
            Country::firstOrCreate(['name' => $name]);
        }
    }
}
