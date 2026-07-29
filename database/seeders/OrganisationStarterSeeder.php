<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class OrganisationStarterSeeder extends Seeder
{
    /**
     * Seed a starter set of departments and positions so a freshly deployed
     * instance isn't completely empty on first login. Generic examples, not
     * a prescribed org chart — the client edits or replaces them entirely
     * under Organisation > Départements / Postes.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'DG', 'name' => 'Direction Générale'],
            ['code' => 'RH', 'name' => 'Ressources Humaines'],
            ['code' => 'FIN', 'name' => 'Finance & Comptabilité'],
            ['code' => 'COM', 'name' => 'Commercial'],
            ['code' => 'OPS', 'name' => 'Opérations'],
            ['code' => 'IT', 'name' => 'Informatique'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(['code' => $department['code']], $department);
        }

        $positions = [
            ['code' => 'DG-01', 'title' => 'Directeur Général', 'department' => 'DG'],
            ['code' => 'RH-01', 'title' => 'Responsable RH', 'department' => 'RH'],
            ['code' => 'RH-02', 'title' => 'Gestionnaire de paie', 'department' => 'RH'],
            ['code' => 'FIN-01', 'title' => 'Comptable', 'department' => 'FIN'],
            ['code' => 'COM-01', 'title' => 'Chargé(e) commercial(e)', 'department' => 'COM'],
            ['code' => 'OPS-01', 'title' => 'Chef d\'équipe', 'department' => 'OPS'],
            ['code' => 'IT-01', 'title' => 'Administrateur systèmes', 'department' => 'IT'],
            ['code' => 'ADM-01', 'title' => 'Assistant(e) administratif(ve)', 'department' => null],
        ];

        $departmentIds = Department::whereIn('code', array_column($positions, 'department'))
            ->pluck('id', 'code');

        foreach ($positions as $position) {
            Position::firstOrCreate(['code' => $position['code']], [
                'title' => $position['title'],
                'department_id' => $position['department'] ? $departmentIds->get($position['department']) : null,
            ]);
        }
    }
}
