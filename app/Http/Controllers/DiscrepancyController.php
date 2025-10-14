<?php

namespace App\Http\Controllers;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\Severity;
use App\Models\Discrepancy;
use App\Models\Staff;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiscrepancyController extends Controller
{
    /**
     * Display list of discrepancies with filters.
     */
    public function index(Request $request): Response
    {
        $query = Discrepancy::with(['staff', 'detectedBy'])
            ->latest('detected_at');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('discrepancy_type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('staff', function ($q2) use ($search) {
                    $q2->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('staff_number', 'like', "%{$search}%");
                })->orWhere('description', 'like', "%{$search}%");
            });
        }

        $discrepancies = $query->paginate(20)->through(fn ($discrepancy) => [
            'id' => $discrepancy->id,
            'staff' => $discrepancy->staff ? [
                'id' => $discrepancy->staff->id,
                'staff_number' => $discrepancy->staff->staff_number,
                'full_name' => $discrepancy->staff->getFullName(),
            ] : null,
            'discrepancy_type' => $discrepancy->discrepancy_type->value,
            'type_label' => $discrepancy->getTypeLabel(),
            'severity' => $discrepancy->severity->value,
            'severity_color' => $discrepancy->getSeverityColor(),
            'status' => $discrepancy->status->value,
            'description' => $discrepancy->description,
            'detected_at' => $discrepancy->detected_at->format('Y-m-d H:i'),
            'days_open' => $discrepancy->detected_at->diffInDays(now()),
            'is_overdue' => $discrepancy->detected_at->addDays(7)->isPast() && $discrepancy->status->isActive(),
            'can_resolve' => $discrepancy->isOpen() || $discrepancy->status === DiscrepancyStatus::UnderReview,
        ]);

        // Get filter options
        $statuses = collect(DiscrepancyStatus::cases())->map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ]);

        $types = collect(DiscrepancyType::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $severities = collect(Severity::cases())->map(fn ($severity) => [
            'value' => $severity->value,
            'label' => $severity->label(),
        ]);

        // Get statistics
        $stats = [
            'total' => Discrepancy::count(),
            'open' => Discrepancy::where('status', DiscrepancyStatus::Open)->count(),
            'under_review' => Discrepancy::where('status', DiscrepancyStatus::UnderReview)->count(),
            'resolved' => Discrepancy::where('status', DiscrepancyStatus::Resolved)->count(),
            'critical' => Discrepancy::where('severity', Severity::Critical)
                ->whereIn('status', [DiscrepancyStatus::Open, DiscrepancyStatus::UnderReview])
                ->count(),
        ];

        return Inertia::render('discrepancies/Index', [
            'discrepancies' => $discrepancies,
            'statuses' => $statuses,
            'types' => $types,
            'severities' => $severities,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'severity', 'staff_id', 'search']),
        ]);
    }

    /**
     * Display a single discrepancy with full details.
     */
    public function show(Discrepancy $discrepancy): Response
    {
        $discrepancy->load([
            'staff.jobTitle.jobGrade',
            'staff.department',
            'staff.station',
            'detectedBy',
            'discrepancyNotes.createdBy',
            'discrepancyResolution.resolvedBy',
        ]);

        return Inertia::render('discrepancies/Show', [
            'discrepancy' => [
                'id' => $discrepancy->id,
                'staff' => $discrepancy->staff ? [
                    'id' => $discrepancy->staff->id,
                    'staff_number' => $discrepancy->staff->staff_number,
                    'full_name' => $discrepancy->staff->getFullName(),
                    'email' => $discrepancy->staff->email,
                    'phone' => $discrepancy->staff->phone_primary,
                    'current_salary' => $discrepancy->staff->current_salary,
                    'job_title' => $discrepancy->staff->jobTitle?->name,
                    'department' => $discrepancy->staff->department?->name,
                    'station' => $discrepancy->staff->station?->name,
                    'employment_status' => $discrepancy->staff->employment_status->value,
                    'is_ghost' => $discrepancy->staff->is_ghost,
                ] : null,
                'discrepancy_type' => $discrepancy->discrepancy_type->value,
                'type_label' => $discrepancy->getTypeLabel(),
                'severity' => $discrepancy->severity->value,
                'severity_color' => $discrepancy->getSeverityColor(),
                'status' => $discrepancy->status->value,
                'description' => $discrepancy->description,
                'detected_by' => $discrepancy->detectedBy ? [
                    'id' => $discrepancy->detectedBy->id,
                    'name' => $discrepancy->detectedBy->name,
                ] : ['name' => 'System'],
                'detected_at' => $discrepancy->detected_at->format('Y-m-d H:i:s'),
                'days_open' => $discrepancy->detected_at->diffInDays(now()),
                'notes' => $discrepancy->discrepancyNotes ? $discrepancy->discrepancyNotes->map(fn ($note) => [
                    'id' => $note->id,
                    'content' => $note->note_content,
                    'is_internal' => $note->is_internal,
                    'created_by' => $note->createdBy->name,
                    'created_at' => $note->created_at->format('Y-m-d H:i'),
                ]) : null,
                'resolution' => $discrepancy->discrepancyResolution ? [
                    'resolution_type' => $discrepancy->discrepancyResolution->resolution_type,
                    'notes' => $discrepancy->discrepancyResolution->resolution_notes,
                    'outcome' => $discrepancy->discrepancyResolution->outcome,
                    'resolved_by' => $discrepancy->discrepancyResolution->resolvedBy->name,
                    'resolved_at' => $discrepancy->discrepancyResolution->resolved_at->format('Y-m-d H:i'),
                ] : null,
                'can_edit' => $discrepancy->isOpen() || $discrepancy->status === DiscrepancyStatus::UnderReview,
                'can_resolve' => $discrepancy->isOpen() || $discrepancy->status === DiscrepancyStatus::UnderReview,
                'can_dismiss' => $discrepancy->isOpen() || $discrepancy->status === DiscrepancyStatus::UnderReview,
            ],
        ]);
    }

    /**
     * Show create discrepancy form (for manual discrepancy creation).
     */
    public function create(): Response
    {
        // Get active staff
        $staff = Staff::where('is_active', true)
            ->orderBy('full_name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'staff_number' => $s->staff_number,
                'full_name' => $s->getFullName(),
                'department' => $s->department?->name,
            ]);

        // Get active stations
        $stations = Station::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($station) => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
            ]);

        $types = collect(DiscrepancyType::cases())->map(fn ($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ]);

        $severities = collect(Severity::cases())->map(fn ($severity) => [
            'value' => $severity->value,
            'label' => $severity->label(),
        ]);

        return Inertia::render('discrepancies/Create', [
            'staff' => $staff,
            'stations' => $stations,
            'types' => $types,
            'severities' => $severities,
        ]);
    }

    /**
     * Store a new manually created discrepancy.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'station_id' => 'nullable|exists:stations,id',
            'discrepancy_type' => ['required', Rule::enum(DiscrepancyType::class)],
            'severity' => ['required', Rule::enum(Severity::class)],
            'description' => 'required|string|min:10|max:1000',
        ]);

        $discrepancy = Discrepancy::create([
            ...$validated,
            'status' => DiscrepancyStatus::Open,
            'detected_by' => auth()->id(),
            'detected_at' => now(),
        ]);

        return redirect()->route('discrepancies.show', $discrepancy)
            ->with('success', 'Discrepancy created successfully.');
    }

    /**
     * Update a discrepancy.
     */
    public function update(Request $request, Discrepancy $discrepancy): RedirectResponse
    {
        if (! $discrepancy->isOpen() && $discrepancy->status !== DiscrepancyStatus::UnderReview) {
            return back()->with('error', 'Cannot update resolved or dismissed discrepancies.');
        }

        $validated = $request->validate([
            'severity' => ['required', Rule::enum(Severity::class)],
            'description' => 'required|string|min:10|max:1000',
        ]);

        $discrepancy->update($validated);

        return back()->with('success', 'Discrepancy updated successfully.');
    }

    /**
     * Soft delete a discrepancy.
     */
    public function destroy(Discrepancy $discrepancy): RedirectResponse
    {
        if ($discrepancy->status === DiscrepancyStatus::Resolved) {
            return back()->with('error', 'Cannot delete resolved discrepancies.');
        }

        $discrepancy->delete();

        return redirect()->route('discrepancies.index')
            ->with('success', 'Discrepancy deleted successfully.');
    }

    /**
     * Mark discrepancy as under review.
     */
    public function markUnderReview(Discrepancy $discrepancy): RedirectResponse
    {
        if (! $discrepancy->isOpen()) {
            return back()->with('error', 'Only open discrepancies can be marked under review.');
        }

        $discrepancy->markUnderReview();

        return back()->with('success', 'Discrepancy marked as under review.');
    }

    /**
     * Resolve a discrepancy.
     */
    public function resolve(Request $request, Discrepancy $discrepancy): RedirectResponse
    {
        if (! $discrepancy->isOpen() && $discrepancy->status !== DiscrepancyStatus::UnderReview) {
            return back()->with('error', 'Only open or under-review discrepancies can be resolved.');
        }

        $validated = $request->validate([
            'resolution_type' => 'required|string|in:correction,verification,dismissal,escalation,other',
            'notes' => 'required|string|min:10|max:1000',
            'outcome' => 'nullable|string|in:resolved,unresolved',
        ]);

        $discrepancy->resolve(
            resolvedBy: auth()->id(),
            resolutionType: $validated['resolution_type'],
            notes: $validated['notes'],
            outcome: $validated['outcome'] ?? 'resolved'
        );

        return back()->with('success', 'Discrepancy resolved successfully.');
    }

    /**
     * Dismiss a discrepancy.
     */
    public function dismiss(Request $request, Discrepancy $discrepancy): RedirectResponse
    {
        if (! $discrepancy->isOpen() && $discrepancy->status !== DiscrepancyStatus::UnderReview) {
            return back()->with('error', 'Only open or under-review discrepancies can be dismissed.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $discrepancy->dismiss($validated['reason'] ?? null);

        return back()->with('success', 'Discrepancy dismissed successfully.');
    }

    /**
     * Add a note to a discrepancy.
     */
    public function addNote(Request $request, Discrepancy $discrepancy): RedirectResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|min:5|max:1000',
            'is_internal' => 'boolean',
        ]);

        $discrepancy->addNote(
            createdBy: auth()->id(),
            content: $validated['content'],
            isInternal: $validated['is_internal'] ?? false
        );

        return back()->with('success', 'Note added successfully.');
    }

    /**
     * Display ghost employees page with enhanced detection info.
     */
    public function ghostEmployees(Request $request): Response
    {
        $query = Discrepancy::where('discrepancy_type', DiscrepancyType::GhostEmployee)
            ->with(['staff.monthlyPayments', 'staff.headcountVerifications', 'staff.station'])
            ->latest('detected_at');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $ghostDiscrepancies = $query->paginate(15)->through(function ($discrepancy) {
            $staff = $discrepancy->staff;

            return [
                'id' => $discrepancy->id,
                'staff' => [
                    'id' => $staff->id,
                    'staff_number' => $staff->staff_number,
                    'full_name' => $staff->getFullName(),
                    'station' => $staff->station?->name,
                    'is_ghost' => $staff->is_ghost,
                ],
                'severity' => $discrepancy->severity->value,
                'severity_color' => $discrepancy->getSeverityColor(),
                'status' => $discrepancy->status->value,
                'description' => $discrepancy->description,
                'detected_at' => $discrepancy->detected_at->format('Y-m-d'),
                'days_open' => $discrepancy->detected_at->diffInDays(now()),
                'payment_count' => $staff->monthlyPayments()
                    ->where('payment_date', '>=', now()->subMonths(3))
                    ->count(),
                'verification_count' => $staff->headcountVerifications()->count(),
                'last_verified' => $staff->last_verified_at?->format('Y-m-d'),
            ];
        });

        // Get statistics
        $stats = [
            'total_ghost_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::GhostEmployee)->count(),
            'open' => Discrepancy::where('discrepancy_type', DiscrepancyType::GhostEmployee)
                ->where('status', DiscrepancyStatus::Open)
                ->count(),
            'resolved' => Discrepancy::where('discrepancy_type', DiscrepancyType::GhostEmployee)
                ->where('status', DiscrepancyStatus::Resolved)
                ->count(),
            'staff_flagged_as_ghost' => Staff::where('is_ghost', true)->count(),
        ];

        return Inertia::render('discrepancies/GhostEmployees', [
            'discrepancies' => $ghostDiscrepancies,
            'stats' => $stats,
            'filters' => $request->only(['status', 'severity']),
        ]);
    }
}
