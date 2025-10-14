<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Run the seeder before each test
    $this->seed(RolesAndPermissionsSeeder::class);

    // Create a test route that requires permission
    Route::middleware(['web', 'auth', 'permission:view staff'])->get('/test-staff-permission', function () {
        return response()->json(['message' => 'Access granted']);
    });

    Route::middleware(['web', 'auth', 'permission:manage users'])->get('/test-admin-permission', function () {
        return response()->json(['message' => 'Admin access granted']);
    });
});

it('allows access when user has required permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view staff');

    $response = $this->actingAs($user)->get('/test-staff-permission');

    $response->assertOk();
    $response->assertJson(['message' => 'Access granted']);
});

it('denies access when user lacks required permission', function () {
    $user = User::factory()->create();
    // User has no permissions

    $response = $this->actingAs($user)->get('/test-staff-permission');

    $response->assertForbidden();
});

it('allows access through role-based permission', function () {
    $user = User::factory()->create();
    $user->assignRole('Field Auditor');

    $response = $this->actingAs($user)->get('/test-staff-permission');

    $response->assertOk();
});

it('denies access when user has different permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view reports');

    $response = $this->actingAs($user)->get('/test-staff-permission');

    $response->assertForbidden();
});

it('allows audit leader to access all protected routes', function () {
    $user = User::factory()->create();
    $user->assignRole('Audit Leader');

    $staffResponse = $this->actingAs($user)->get('/test-staff-permission');
    $adminResponse = $this->actingAs($user)->get('/test-admin-permission');

    $staffResponse->assertOk();
    $adminResponse->assertOk();
});

it('denies field auditor from admin routes', function () {
    $user = User::factory()->create();
    $user->assignRole('Field Auditor');

    $response = $this->actingAs($user)->get('/test-admin-permission');

    $response->assertForbidden();
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/test-staff-permission');

    $response->assertRedirect();
});
