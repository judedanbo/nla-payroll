<?php

use App\Models\ImportHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Storage::fake('local');
    Queue::fake();
});

test('import index page loads successfully', function () {
    $response = $this->get('/import');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('import/Index')
        ->has('recentImports')
    );
});

test('import history page loads successfully', function () {
    $response = $this->get('/import/history');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('import/History')
        ->has('imports')
        ->has('filters')
    );
});

test('can upload valid csv file', function () {
    $csv = UploadedFile::fake()->create('test.csv', 100, 'text/csv');

    $response = $this->post('/import/upload', [
        'file' => $csv,
        'import_type' => 'staff',
    ]);

    $response->assertRedirect('/import/preview');
    expect(session()->has('import_file_path'))->toBeTrue();
    expect(session()->has('import_preview'))->toBeTrue();
});

test('file upload validates file type', function () {
    $pdf = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

    $response = $this->post('/import/upload', [
        'file' => $pdf,
        'import_type' => 'staff',
    ]);

    $response->assertSessionHasErrors('file');
});

test('file upload validates file size', function () {
    $largeCsv = UploadedFile::fake()->create('large.csv', 11000, 'text/csv'); // 11MB

    $response = $this->post('/import/upload', [
        'file' => $largeCsv,
        'import_type' => 'staff',
    ]);

    $response->assertSessionHasErrors('file');
});

test('file upload validates import type', function () {
    $csv = UploadedFile::fake()->create('test.csv', 100, 'text/csv');

    $response = $this->post('/import/upload', [
        'file' => $csv,
        'import_type' => 'invalid_type',
    ]);

    $response->assertSessionHasErrors('import_type');
});

test('preview page shows uploaded file data', function () {
    session([
        'import_file_path' => 'test/path.csv',
        'import_file_name' => 'test.csv',
        'import_type' => 'staff',
        'import_preview' => [
            'headers' => ['Name', 'Email'],
            'rows' => [['John Doe', 'john@example.com']],
        ],
    ]);

    $response = $this->get('/import/preview');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('import/Preview')
        ->has('fileName')
        ->has('importType')
        ->has('headers')
        ->has('rows')
        ->has('expectedColumns')
        ->has('suggestedMapping')
    );
});

test('preview page redirects if no file uploaded', function () {
    $response = $this->get('/import/preview');

    $response->assertRedirect('/import');
    $response->assertSessionHas('error');
});

test('can process import with valid column mapping', function () {
    session([
        'import_file_path' => 'test/path.csv',
        'import_file_name' => 'test.csv',
        'import_type' => 'staff',
    ]);

    $response = $this->post('/import/process', [
        'column_mapping' => [
            'Name' => 'first_name',
            'Email' => 'email',
        ],
        'skip_duplicates' => true,
        'validate_only' => false,
    ]);

    $response->assertRedirect();

    expect(ImportHistory::count())->toBe(1);

    $import = ImportHistory::first();
    expect($import->user_id)->toBe($this->user->id);
    expect($import->import_type)->toBe('staff');
    expect($import->status)->toBe('pending');
});

test('process import validates column mapping', function () {
    session([
        'import_file_path' => 'test/path.csv',
        'import_file_name' => 'test.csv',
        'import_type' => 'staff',
    ]);

    $response = $this->post('/import/process', [
        'column_mapping' => [],
        'skip_duplicates' => true,
    ]);

    $response->assertSessionHasErrors('column_mapping');
});

test('process import requires file in session', function () {
    $response = $this->post('/import/process', [
        'column_mapping' => ['Name' => 'first_name'],
        'skip_duplicates' => true,
    ]);

    $response->assertRedirect('/import');
    $response->assertSessionHas('error');
});

test('can view import details', function () {
    $import = ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
    ]);

    $response = $this->get("/import/{$import->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('import/Show')
        ->has('import')
        ->where('import.id', $import->id)
    );
});

test('import history filters by status', function () {
    ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
    ]);

    ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'failed',
    ]);

    $response = $this->get('/import/history?status=completed');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('import/History')
        ->has('imports.data', 1)
        ->where('filters.status', 'completed')
    );
});

test('import history filters by import type', function () {
    ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'import_type' => 'staff',
    ]);

    ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'import_type' => 'bank_details',
    ]);

    $response = $this->get('/import/history?import_type=staff');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('import/History')
        ->has('imports.data', 1)
        ->where('filters.import_type', 'staff')
    );
});

test('can rollback completed import', function () {
    $import = ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'successful_records' => 10,
        'rolled_back_at' => null,
    ]);

    $response = $this->post("/import/{$import->id}/rollback");

    $response->assertSessionHas('success');

    $import->refresh();
    expect($import->rolled_back_at)->not->toBeNull();
});

test('cannot rollback incomplete import', function () {
    $import = ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'processing',
    ]);

    $response = $this->post("/import/{$import->id}/rollback");

    $response->assertSessionHas('error');

    $import->refresh();
    expect($import->rolled_back_at)->toBeNull();
});

test('cannot rollback already rolled back import', function () {
    $import = ImportHistory::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
        'rolled_back_at' => now(),
    ]);

    $response = $this->post("/import/{$import->id}/rollback");

    $response->assertSessionHas('error');
});

test('can download import errors as csv', function () {
    $import = ImportHistory::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $import->errors()->create([
        'row_number' => 1,
        'field_name' => 'email',
        'error_message' => 'Invalid email format',
        'row_data' => ['email' => 'invalid-email'],
    ]);

    $response = $this->get("/import/{$import->id}/errors");

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('Invalid email format');
});

test('requires authentication for all import routes', function () {
    auth()->logout();

    $this->get('/import')->assertRedirect('/login');
    $this->get('/import/history')->assertRedirect('/login');
    $this->get('/import/preview')->assertRedirect('/login');
    $this->post('/import/upload', [])->assertRedirect('/login');
    $this->post('/import/process', [])->assertRedirect('/login');
});
