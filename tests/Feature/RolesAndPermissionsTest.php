<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run the seeder before each test
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates all 5 roles', function () {
    expect(Role::count())->toBe(5);

    expect(Role::whereName('Audit Leader')->exists())->toBeTrue();
    expect(Role::whereName('Field Auditor')->exists())->toBeTrue();
    expect(Role::whereName('HR Liaison')->exists())->toBeTrue();
    expect(Role::whereName('IT Support')->exists())->toBeTrue();
    expect(Role::whereName('Report Viewer')->exists())->toBeTrue();
});

it('creates all permissions', function () {
    $expectedPermissions = [
        'view staff',
        'create staff',
        'edit staff',
        'delete staff',
        'export staff',
        'import staff',
        'view payroll',
        'create payroll',
        'edit payroll',
        'delete payroll',
        'export payroll',
        'view audits',
        'create audits',
        'edit audits',
        'delete audits',
        'verify headcount',
        'upload verification photos',
        'record gps location',
        'flag discrepancies',
        'resolve discrepancies',
        'view reports',
        'generate reports',
        'export reports',
        'manage settings',
        'manage users',
        'manage roles',
        'manage permissions',
        'view audit trail',
        'system configuration',
        'technical support',
    ];

    expect(Permission::count())->toBe(count($expectedPermissions));

    foreach ($expectedPermissions as $permission) {
        expect(Permission::whereName($permission)->exists())->toBeTrue();
    }
});

it('assigns user to audit leader role', function () {
    $user = User::factory()->create();
    $user->assignRole('Audit Leader');

    expect($user->hasRole('Audit Leader'))->toBeTrue();
    expect($user->hasPermissionTo('view staff'))->toBeTrue();
    expect($user->hasPermissionTo('create staff'))->toBeTrue();
    expect($user->hasPermissionTo('manage users'))->toBeTrue();
});

it('audit leader has all permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('Audit Leader');

    $allPermissions = Permission::all()->pluck('name');

    foreach ($allPermissions as $permission) {
        expect($user->hasPermissionTo($permission))->toBeTrue();
    }
});

it('field auditor has limited permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('Field Auditor');

    // Should have these permissions
    expect($user->hasPermissionTo('view staff'))->toBeTrue();
    expect($user->hasPermissionTo('verify headcount'))->toBeTrue();
    expect($user->hasPermissionTo('upload verification photos'))->toBeTrue();
    expect($user->hasPermissionTo('record gps location'))->toBeTrue();
    expect($user->hasPermissionTo('flag discrepancies'))->toBeTrue();

    // Should NOT have these permissions
    expect($user->hasPermissionTo('delete staff'))->toBeFalse();
    expect($user->hasPermissionTo('manage users'))->toBeFalse();
    expect($user->hasPermissionTo('view payroll'))->toBeFalse();
});

it('hr liaison has read-only permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('HR Liaison');

    // Should have read permissions
    expect($user->hasPermissionTo('view staff'))->toBeTrue();
    expect($user->hasPermissionTo('view payroll'))->toBeTrue();
    expect($user->hasPermissionTo('view audits'))->toBeTrue();
    expect($user->hasPermissionTo('view reports'))->toBeTrue();

    // Should NOT have write permissions
    expect($user->hasPermissionTo('create staff'))->toBeFalse();
    expect($user->hasPermissionTo('edit staff'))->toBeFalse();
    expect($user->hasPermissionTo('delete staff'))->toBeFalse();
    expect($user->hasPermissionTo('manage users'))->toBeFalse();
});

it('it support has system permissions only', function () {
    $user = User::factory()->create();
    $user->assignRole('IT Support');

    // Should have system permissions
    expect($user->hasPermissionTo('system configuration'))->toBeTrue();
    expect($user->hasPermissionTo('technical support'))->toBeTrue();
    expect($user->hasPermissionTo('view audit trail'))->toBeTrue();
    expect($user->hasPermissionTo('manage users'))->toBeTrue();

    // Should NOT have data permissions
    expect($user->hasPermissionTo('view staff'))->toBeFalse();
    expect($user->hasPermissionTo('view payroll'))->toBeFalse();
    expect($user->hasPermissionTo('view audits'))->toBeFalse();
});

it('report viewer has report permissions only', function () {
    $user = User::factory()->create();
    $user->assignRole('Report Viewer');

    // Should have report permissions
    expect($user->hasPermissionTo('view reports'))->toBeTrue();
    expect($user->hasPermissionTo('export reports'))->toBeTrue();

    // Should NOT have other permissions
    expect($user->hasPermissionTo('view staff'))->toBeFalse();
    expect($user->hasPermissionTo('view payroll'))->toBeFalse();
    expect($user->hasPermissionTo('manage users'))->toBeFalse();
    expect($user->hasPermissionTo('generate reports'))->toBeFalse();
});

it('can assign multiple roles to a user', function () {
    $user = User::factory()->create();
    $user->assignRole(['Field Auditor', 'Report Viewer']);

    expect($user->hasRole('Field Auditor'))->toBeTrue();
    expect($user->hasRole('Report Viewer'))->toBeTrue();
    expect($user->hasPermissionTo('verify headcount'))->toBeTrue();
    expect($user->hasPermissionTo('view reports'))->toBeTrue();
});

it('can directly assign permissions to a user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view staff');

    expect($user->hasPermissionTo('view staff'))->toBeTrue();
    expect($user->hasPermissionTo('create staff'))->toBeFalse();
});

it('can revoke role from user', function () {
    $user = User::factory()->create();
    $user->assignRole('Field Auditor');

    expect($user->hasRole('Field Auditor'))->toBeTrue();

    $user->removeRole('Field Auditor');

    expect($user->hasRole('Field Auditor'))->toBeFalse();
    expect($user->hasPermissionTo('verify headcount'))->toBeFalse();
});

it('can revoke permission from user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view staff');

    expect($user->hasPermissionTo('view staff'))->toBeTrue();

    $user->revokePermissionTo('view staff');

    expect($user->hasPermissionTo('view staff'))->toBeFalse();
});
