<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'organisation.view',
            'organisation.manage',
            'employees.view',
            'employees.manage',
            'employees.anonymize',
            'administration.manage',
            'leaves.manage',
            'documents.manage',
            'attendance.manage',
            'payroll.manage',
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::findOrCreate('super-admin');
        $superAdmin->syncPermissions($permissions);

        $rhAdmin = Role::findOrCreate('rh-admin');
        $rhAdmin->syncPermissions([
            'organisation.view',
            'organisation.manage',
            'employees.view',
            'employees.manage',
            'employees.anonymize',
            'leaves.manage',
            'documents.manage',
            'attendance.manage',
            'payroll.manage',
            'reports.view',
        ]);

        $manager = Role::findOrCreate('manager');
        $manager->syncPermissions(['organisation.view', 'employees.view']);

        Role::findOrCreate('employe');
    }
}
