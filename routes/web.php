<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Import Routes
Route::middleware(['auth', 'verified'])->prefix('import')->name('import.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::post('upload', [ImportController::class, 'upload'])->name('upload');
    Route::get('preview', [ImportController::class, 'preview'])->name('preview');
    Route::post('process', [ImportController::class, 'process'])->name('process');
    Route::get('history', [ImportController::class, 'history'])->name('history');
    Route::get('{importHistory}', [ImportController::class, 'show'])->name('show');
    Route::get('{importHistory}/errors', [ImportController::class, 'downloadErrors'])->name('downloadErrors');
    Route::post('{importHistory}/rollback', [ImportController::class, 'rollback'])->name('rollback');
});

// Headcount Management Routes
Route::middleware(['auth', 'verified'])->prefix('headcount')->name('headcount.')->group(function () {
    // Session Management
    Route::get('/', [\App\Http\Controllers\HeadcountController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\HeadcountController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\HeadcountController::class, 'store'])->name('store');
    Route::get('{session}', [\App\Http\Controllers\HeadcountController::class, 'show'])->name('show');
    Route::post('{session}/start', [\App\Http\Controllers\HeadcountController::class, 'startSession'])->name('start');
    Route::post('{session}/end', [\App\Http\Controllers\HeadcountController::class, 'endSession'])->name('end');
    Route::post('{session}/pause', [\App\Http\Controllers\HeadcountController::class, 'pauseSession'])->name('pause');

    // Verification
    Route::get('{session}/verify', [\App\Http\Controllers\HeadcountController::class, 'verificationForm'])->name('verify');
    Route::post('verify', [\App\Http\Controllers\HeadcountController::class, 'captureVerification'])->name('capture');
    Route::post('bulk-verify', [\App\Http\Controllers\HeadcountController::class, 'bulkVerify'])->name('bulk-verify');
    Route::get('staff/{staff}/history', [\App\Http\Controllers\HeadcountController::class, 'verificationHistory'])->name('history');

    // Reports
    Route::get('{session}/report', [\App\Http\Controllers\HeadcountController::class, 'sessionReport'])->name('report');
});

// Team Assignment Routes
Route::middleware(['auth', 'verified'])->prefix('assignments')->name('assignments.')->group(function () {
    Route::get('/', [\App\Http\Controllers\TeamAssignmentController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\TeamAssignmentController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\TeamAssignmentController::class, 'store'])->name('store');
    Route::put('{assignment}', [\App\Http\Controllers\TeamAssignmentController::class, 'update'])->name('update');
    Route::delete('{assignment}', [\App\Http\Controllers\TeamAssignmentController::class, 'destroy'])->name('destroy');
    Route::get('coverage', [\App\Http\Controllers\TeamAssignmentController::class, 'getStationCoverage'])->name('coverage');
    Route::post('{assignment}/reassign', [\App\Http\Controllers\TeamAssignmentController::class, 'reassign'])->name('reassign');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
