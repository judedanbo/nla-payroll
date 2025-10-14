<?php

use App\Enums\HeadcountSessionStatus;
use App\Models\HeadcountSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can start a planned session', function () {
    $session = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Planned]);

    $response = $this->post("/headcount/{$session->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Headcount session started successfully.');

    $session->refresh();
    expect($session->status)->toBe(HeadcountSessionStatus::InProgress);
});

test('cannot start a session that is already in progress', function () {
    $session = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::InProgress]);

    $response = $this->post("/headcount/{$session->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Only pending sessions can be started.');

    $session->refresh();
    expect($session->status)->toBe(HeadcountSessionStatus::InProgress);
});

test('cannot start a completed session', function () {
    $session = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->completed()
        ->create();

    $response = $this->post("/headcount/{$session->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Only pending sessions can be started.');

    $session->refresh();
    expect($session->status)->toBe(HeadcountSessionStatus::Completed);
});

test('cannot start a cancelled session', function () {
    $session = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Cancelled]);

    $response = $this->post("/headcount/{$session->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Only pending sessions can be started.');

    $session->refresh();
    expect($session->status)->toBe(HeadcountSessionStatus::Cancelled);
});

test('cannot start a session when another session is already in progress', function () {
    // Create an active session
    $activeSession = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::InProgress]);

    // Try to start another planned session
    $plannedSession = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Planned]);

    $response = $this->post("/headcount/{$plannedSession->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Another session is already in progress. Please complete it first.');

    $plannedSession->refresh();
    expect($plannedSession->status)->toBe(HeadcountSessionStatus::Planned);
});

test('can start a session when previous sessions are completed', function () {
    // Create a completed session
    HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->completed()
        ->create();

    // Try to start a new planned session
    $plannedSession = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Planned]);

    $response = $this->post("/headcount/{$plannedSession->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Headcount session started successfully.');

    $plannedSession->refresh();
    expect($plannedSession->status)->toBe(HeadcountSessionStatus::InProgress);
});

test('requires authentication to start a session', function () {
    auth()->logout();

    $session = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Planned]);

    $response = $this->post("/headcount/{$session->id}/start");

    $response->assertRedirect('/login');

    $session->refresh();
    expect($session->status)->toBe(HeadcountSessionStatus::Planned);
});

test('returns 404 for non-existent session', function () {
    $response = $this->post('/headcount/99999/start');

    $response->assertNotFound();
});

test('cannot start paused session', function () {
    $session = HeadcountSession::factory()
        ->for($this->user, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Paused]);

    $response = $this->post("/headcount/{$session->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Only pending sessions can be started.');

    $session->refresh();
    expect($session->status)->toBe(HeadcountSessionStatus::Paused);
});

test('multiple users can have their sessions planned without conflict', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $session1 = HeadcountSession::factory()
        ->for($user1, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Planned]);

    $session2 = HeadcountSession::factory()
        ->for($user2, 'createdBy')
        ->create(['status' => HeadcountSessionStatus::Planned]);

    // User 1 starts their session
    $this->actingAs($user1);
    $response = $this->post("/headcount/{$session1->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $session1->refresh();
    expect($session1->status)->toBe(HeadcountSessionStatus::InProgress);

    // User 2 cannot start their session because user 1's is active
    $this->actingAs($user2);
    $response = $this->post("/headcount/{$session2->id}/start");

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Another session is already in progress. Please complete it first.');

    $session2->refresh();
    expect($session2->status)->toBe(HeadcountSessionStatus::Planned);
});
