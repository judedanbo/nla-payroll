<?php

namespace App\Http\Controllers;

use App\Http\Requests\Import\FileUploadRequest;
use App\Http\Requests\Import\ProcessImportRequest;
use App\Jobs\ProcessCSVImport;
use App\Models\ImportHistory;
use App\Services\CSVImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        private CSVImportService $csvImportService
    ) {}

    /**
     * Display the import interface with recent import history.
     */
    public function index(): Response
    {
        $recentImports = ImportHistory::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($import) => [
                'id' => $import->id,
                'import_type' => $import->import_type,
                'file_name' => $import->file_name,
                'status' => $import->status,
                'total_records' => $import->total_records,
                'successful_records' => $import->successful_records,
                'failed_records' => $import->failed_records,
                'success_rate' => $import->getSuccessRate(),
                'created_at' => $import->created_at,
                'user' => [
                    'name' => $import->user->name,
                ],
            ]);

        return Inertia::render('import/Index', [
            'recentImports' => $recentImports,
        ]);
    }

    /**
     * Handle file upload and initial validation.
     */
    public function upload(FileUploadRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $request->file('file');

        // Store uploaded file temporarily (Laravel will create directory if needed)
        $path = $file->store('imports/temp', 'local');

        // Parse CSV headers and first 10 rows for preview
        // Get the full path from Storage facade since 'local' disk uses storage_path('app/private')
        $fullPath = Storage::disk('local')->path($path);
        $preview = $this->csvImportService->previewFile($fullPath);

        // Store preview data in session for next step
        session([
            'import_file_path' => $path,
            'import_file_name' => $file->getClientOriginalName(),
            'import_type' => $validated['import_type'],
            'import_preview' => $preview,
        ]);

        return redirect()->route('import.preview');
    }

    /**
     * Display preview of first 10 rows with column mapping interface.
     */
    public function preview()
    {
        $preview = session('import_preview');
        $importType = session('import_type');
        $fileName = session('import_file_name');

        if (! $preview) {
            return redirect()->route('import.index')
                ->with('error', 'No file uploaded. Please upload a file first.');
        }

        // Get expected columns based on import type
        $expectedColumns = $this->csvImportService->getExpectedColumns($importType);

        // Attempt auto-mapping
        $suggestedMapping = $this->csvImportService->autoMapColumns(
            $preview['headers'],
            $expectedColumns
        );

        return Inertia::render('import/Preview', [
            'fileName' => $fileName,
            'importType' => $importType,
            'headers' => $preview['headers'],
            'rows' => $preview['rows'],
            'expectedColumns' => $expectedColumns,
            'suggestedMapping' => $suggestedMapping,
        ]);
    }

    /**
     * Queue import job with validated column mapping.
     */
    public function process(ProcessImportRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $filePath = session('import_file_path');
        $fileName = session('import_file_name');
        $importType = session('import_type');

        if (! $filePath) {
            return redirect()->route('import.index')
                ->with('error', 'No file to process. Please upload a file first.');
        }

        // Create import history record
        $importHistory = ImportHistory::create([
            'uploaded_by' => auth()->id(),
            'import_type' => $importType,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'status' => 'pending',
            'total_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
        ]);

        // Dispatch queue job
        ProcessCSVImport::dispatch($importHistory, $validated['column_mapping']);

        // Clear session data
        session()->forget(['import_file_path', 'import_file_name', 'import_type', 'import_preview']);

        return redirect()->route('import.show', $importHistory)
            ->with('success', 'Import started. Processing in background...');
    }

    /**
     * Display all import history with filters.
     */
    public function history(Request $request): Response
    {
        $query = ImportHistory::with('user')->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('import_type')) {
            $query->where('import_type', $request->import_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $imports = $query->paginate(20)->through(fn ($import) => [
            'id' => $import->id,
            'import_type' => $import->import_type,
            'file_name' => $import->file_name,
            'status' => $import->status,
            'total_records' => $import->total_records,
            'successful_records' => $import->successful_records,
            'failed_records' => $import->failed_records,
            'success_rate' => $import->getSuccessRate(),
            'can_rollback' => $import->isCompleted() && ! $import->rolled_back_at,
            'rolled_back_at' => $import->rolled_back_at,
            'created_at' => $import->created_at,
            'completed_at' => $import->completed_at,
            'user' => [
                'name' => $import->user->name,
            ],
        ]);

        return Inertia::render('import/History', [
            'imports' => $imports,
            'filters' => $request->only(['status', 'import_type', 'date_from', 'date_to']),
        ]);
    }

    /**
     * View details of a specific import including errors.
     */
    public function show(ImportHistory $importHistory): Response
    {
        $importHistory->load(['user', 'errors' => function ($query) {
            $query->latest()->limit(100);
        }]);

        return Inertia::render('import/Show', [
            'import' => [
                'id' => $importHistory->id,
                'import_type' => $importHistory->import_type,
                'file_name' => $importHistory->file_name,
                'status' => $importHistory->status,
                'total_records' => $importHistory->total_records,
                'successful_records' => $importHistory->successful_records,
                'failed_records' => $importHistory->failed_records,
                'success_rate' => $importHistory->getSuccessRate(),
                'column_mapping' => $importHistory->column_mapping,
                'options' => $importHistory->options,
                'can_rollback' => $importHistory->isCompleted() && ! $importHistory->rolled_back_at,
                'rolled_back_at' => $importHistory->rolled_back_at,
                'created_at' => $importHistory->created_at,
                'completed_at' => $importHistory->completed_at,
                'user' => [
                    'name' => $importHistory->user->name,
                ],
                'errors' => $importHistory->errors->map(fn ($error) => [
                    'id' => $error->id,
                    'row_number' => $error->row_number,
                    'field_name' => $error->field_name,
                    'error_message' => $error->error_message,
                    'row_data' => $error->row_data,
                ]),
                'has_more_errors' => $importHistory->failed_records > 100,
            ],
        ]);
    }

    /**
     * Download error CSV for failed records.
     */
    public function downloadErrors(ImportHistory $importHistory): StreamedResponse
    {
        $errors = $importHistory->errors()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"import-errors-{$importHistory->id}.csv\"",
        ];

        $callback = function () use ($errors) {
            $file = fopen('php://output', 'w');

            // Write headers
            fputcsv($file, ['Row Number', 'Field Name', 'Error Message', 'Row Data']);

            // Write error rows
            foreach ($errors as $error) {
                fputcsv($file, [
                    $error->row_number,
                    $error->field_name,
                    $error->error_message,
                    json_encode($error->row_data),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Rollback an import by soft deleting all imported records.
     */
    public function rollback(ImportHistory $importHistory): RedirectResponse
    {
        if (! $importHistory->isCompleted()) {
            return back()->with('error', 'Cannot rollback an incomplete import.');
        }

        if ($importHistory->rolled_back_at) {
            return back()->with('error', 'This import has already been rolled back.');
        }

        // Soft delete all imported records
        $importHistory->importedRecords()->delete();

        // Mark import as rolled back
        $importHistory->update([
            'rolled_back_at' => now(),
        ]);

        return back()->with('success', "Import rolled back successfully. {$importHistory->successful_records} records removed.");
    }

    /**
     * Download CSV template for a specific import type.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $type = $request->route('type');

        // Validate import type
        $validTypes = ['staff', 'bank_details', 'monthly_payments'];
        if (! in_array($type, $validTypes)) {
            abort(404, 'Invalid template type');
        }

        $filePath = storage_path("app/samples/{$type}_import_template.csv");

        if (! file_exists($filePath)) {
            abort(404, 'Template file not found');
        }

        $fileName = match ($type) {
            'staff' => 'staff_import_template.csv',
            'bank_details' => 'bank_details_import_template.csv',
            'monthly_payments' => 'monthly_payments_import_template.csv',
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($filePath) {
            $file = fopen($filePath, 'r');
            fpassthru($file);
            fclose($file);
        }, 200, $headers);
    }
}
