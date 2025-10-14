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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
