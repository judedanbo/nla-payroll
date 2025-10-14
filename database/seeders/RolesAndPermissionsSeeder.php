<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for staff management
        $staffPermissions = [
            'view staff',
            'create staff',
            'edit staff',
            'delete staff',
            'export staff',
            'import staff',
        ];

        // Create permissions for payroll management
        $payrollPermissions = [
            'view payroll',
            'create payroll',
            'edit payroll',
            'delete payroll',
            'export payroll',
        ];

        // Create permissions for audit verification
        $auditPermissions = [
            'view audits',
            'create audits',
            'edit audits',
            'delete audits',
            'verify headcount',
            'upload verification photos',
            'record gps location',
            'flag discrepancies',
            'resolve discrepancies',
        ];

        // Create permissions for reports
        $reportPermissions = [
            'view reports',
            'generate reports',
            'export reports',
        ];

        // Create permissions for settings and system
        $systemPermissions = [
            'manage settings',
            'manage users',
            'manage roles',
            'manage permissions',
            'view audit trail',
            'system configuration',
            'technical support',
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $staffPermissions,
            $payrollPermissions,
            $auditPermissions,
            $reportPermissions,
            $systemPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. Audit Leader - Full access to all features
        $auditLeader = Role::create(['name' => 'Audit Leader']);
        $auditLeader->givePermissionTo($allPermissions);

        // 2. Field Auditor - Station-specific access for conducting headcount verification
        $fieldAuditor = Role::create(['name' => 'Field Auditor']);
        $fieldAuditor->givePermissionTo([
            'view staff',
            'verify headcount',
            'upload verification photos',
            'record gps location',
            'flag discrepancies',
            'view audits',
            'create audits',
        ]);

        // 3. HR Liaison - Read-only access to view data
        $hrLiaison = Role::create(['name' => 'HR Liaison']);
        $hrLiaison->givePermissionTo([
            'view staff',
            'view payroll',
            'view audits',
            'view reports',
        ]);

        // 4. IT Support - System administration and technical support
        $itSupport = Role::create(['name' => 'IT Support']);
        $itSupport->givePermissionTo([
            'system configuration',
            'technical support',
            'view audit trail',
            'manage users',
        ]);

        // 5. Report Viewer - Report access only
        $reportViewer = Role::create(['name' => 'Report Viewer']);
        $reportViewer->givePermissionTo([
            'view reports',
            'export reports',
        ]);
    }
}
