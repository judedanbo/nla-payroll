<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Headcount Management
            'view headcount sessions',
            'create headcount sessions',
            'edit headcount sessions',
            'delete headcount sessions',
            'verify staff',
            'approve verifications',

            // Staff Management
            'view staff',
            'create staff',
            'edit staff',
            'delete staff',
            'export staff',

            // Discrepancy Management
            'view discrepancies',
            'create discrepancies',
            'edit discrepancies',
            'resolve discrepancies',
            'dismiss discrepancies',

            // Team Assignments
            'view assignments',
            'create assignments',
            'edit assignments',
            'delete assignments',
            'reassign teams',

            // Import/Export
            'import data',
            'export data',
            'view import history',
            'rollback imports',

            // Reports
            'view reports',
            'generate reports',
            'export reports',

            // System Administration
            'manage users',
            'manage roles',
            'manage permissions',
            'view audit logs',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // 2. Audit Manager - Manages audit teams and reviews
        $auditManager = Role::firstOrCreate(['name' => 'Audit Manager']);
        $auditManager->givePermissionTo([
            'view headcount sessions',
            'create headcount sessions',
            'edit headcount sessions',
            'delete headcount sessions',
            'approve verifications',
            'view staff',
            'view discrepancies',
            'create discrepancies',
            'edit discrepancies',
            'resolve discrepancies',
            'view assignments',
            'create assignments',
            'edit assignments',
            'delete assignments',
            'reassign teams',
            'view reports',
            'generate reports',
            'export reports',
            'view import history',
            'view audit logs',
        ]);

        // 3. Audit Leader - Leads field audit teams
        $auditLeader = Role::firstOrCreate(['name' => 'Audit Leader']);
        $auditLeader->givePermissionTo([
            'view headcount sessions',
            'verify staff',
            'approve verifications',
            'view staff',
            'view discrepancies',
            'create discrepancies',
            'edit discrepancies',
            'view assignments',
            'view reports',
            'generate reports',
        ]);

        // 4. Field Auditor - Conducts field verifications
        $fieldAuditor = Role::firstOrCreate(['name' => 'Field Auditor']);
        $fieldAuditor->givePermissionTo([
            'view headcount sessions',
            'verify staff',
            'view staff',
            'view discrepancies',
            'create discrepancies',
            'view assignments',
            'view reports',
        ]);

        // 5. Data Entry Clerk - Handles imports and data entry
        $dataEntry = Role::firstOrCreate(['name' => 'Data Entry Clerk']);
        $dataEntry->givePermissionTo([
            'view staff',
            'create staff',
            'edit staff',
            'import data',
            'export data',
            'view import history',
        ]);

        // 6. Report Viewer - Read-only access to reports
        $reportViewer = Role::firstOrCreate(['name' => 'Report Viewer']);
        $reportViewer->givePermissionTo([
            'view headcount sessions',
            'view staff',
            'view discrepancies',
            'view assignments',
            'view reports',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: Super Admin, Audit Manager, Audit Leader, Field Auditor, Data Entry Clerk, Report Viewer');
    }
}
