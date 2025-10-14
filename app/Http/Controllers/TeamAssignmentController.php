<?php

namespace App\Http\Controllers;

use App\Models\HeadcountSession;
use App\Models\Station;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamAssignmentController extends Controller
{
    /**
     * Display list of all team assignments with filters.
     */
    public function index(Request $request): Response
    {
        $query = TeamAssignment::with(['user', 'station.region', 'headcountSession', 'assignedBy'])
            ->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('station_id')) {
            $query->where('station_id', $request->station_id);
        }

        if ($request->filled('session_id')) {
            $query->where('headcount_session_id', $request->session_id);
        }

        $assignments = $query->paginate(20)->through(fn ($assignment) => [
            'id' => $assignment->id,
            'user' => [
                'id' => $assignment->user->id,
                'name' => $assignment->user->name,
                'email' => $assignment->user->email,
            ],
            'station' => [
                'id' => $assignment->station->id,
                'name' => $assignment->station->name,
                'code' => $assignment->station->code,
                'city' => $assignment->station->city,
                'region' => $assignment->station->region->name,
            ],
            'session' => $assignment->headcountSession ? [
                'id' => $assignment->headcountSession->id,
                'session_name' => $assignment->headcountSession->session_name,
            ] : null,
            'start_date' => $assignment->start_date->format('Y-m-d'),
            'end_date' => $assignment->end_date?->format('Y-m-d'),
            'status' => $assignment->status,
            'assigned_by' => [
                'name' => $assignment->assignedBy->name,
            ],
            'is_active' => $assignment->isActive(),
            'can_cancel' => $assignment->isActive() || $assignment->status === 'pending',
        ]);

        // Get users for filter (auditors only)
        $users = User::role(['Audit Leader', 'Field Auditor'])
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        // Get stations for filter
        $stations = Station::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($station) => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
            ]);

        return Inertia::render('assignments/Index', [
            'assignments' => $assignments,
            'users' => $users,
            'stations' => $stations,
            'filters' => $request->only(['status', 'user_id', 'station_id', 'session_id']),
        ]);
    }

    /**
     * Show create assignment form.
     */
    public function create(): Response
    {
        // Get auditors only
        $users = User::role(['Audit Leader', 'Field Auditor'])
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        // Get active stations
        $stations = Station::where('is_active', true)
            ->with('region')
            ->orderBy('name')
            ->get()
            ->map(fn ($station) => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'city' => $station->city,
                'region' => $station->region->name,
                'expected_headcount' => $station->expected_headcount,
            ]);

        // Get active sessions
        $sessions = HeadcountSession::whereIn('status', ['pending', 'in_progress'])
            ->orderBy('session_name')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'session_name' => $session->session_name,
                'start_date' => $session->start_date->format('Y-m-d'),
                'end_date' => $session->end_date?->format('Y-m-d'),
            ]);

        return Inertia::render('assignments/Create', [
            'users' => $users,
            'stations' => $stations,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Store a new team assignment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'station_id' => 'required|exists:stations,id',
            'headcount_session_id' => 'nullable|exists:headcount_sessions,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        // Check for conflicts
        $conflicts = $this->checkConflicts(
            $validated['user_id'],
            $validated['start_date'],
            $validated['end_date']
        );

        if ($conflicts->count() > 0) {
            return back()
                ->withInput()
                ->with('error', 'User already has an overlapping assignment during this period.');
        }

        TeamAssignment::create([
            ...$validated,
            'status' => 'active',
            'assigned_by' => auth()->id(),
        ]);

        return redirect()->route('assignments.index')
            ->with('success', 'Team assignment created successfully.');
    }

    /**
     * Update an existing assignment.
     */
    public function update(Request $request, TeamAssignment $assignment): RedirectResponse
    {
        if ($assignment->isCompleted()) {
            return back()->with('error', 'Cannot update completed assignments.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => ['required', Rule::in(['active', 'pending', 'completed', 'cancelled'])],
        ]);

        // Check for conflicts if dates changed
        if ($validated['start_date'] !== $assignment->start_date->format('Y-m-d') ||
            ($validated['end_date'] ?? null) !== $assignment->end_date?->format('Y-m-d')) {
            $conflicts = $this->checkConflicts(
                $assignment->user_id,
                $validated['start_date'],
                $validated['end_date'],
                $assignment->id
            );

            if ($conflicts->count() > 0) {
                return back()
                    ->withInput()
                    ->with('error', 'Update would create an overlapping assignment.');
            }
        }

        $assignment->update($validated);

        return back()->with('success', 'Assignment updated successfully.');
    }

    /**
     * Cancel an assignment.
     */
    public function destroy(TeamAssignment $assignment): RedirectResponse
    {
        if ($assignment->isCompleted()) {
            return back()->with('error', 'Cannot cancel completed assignments.');
        }

        $assignment->cancel();

        return back()->with('success', 'Assignment cancelled successfully.');
    }

    /**
     * Check for assignment conflicts.
     */
    private function checkConflicts(int $userId, string $startDate, ?string $endDate, ?int $excludeId = null)
    {
        $query = TeamAssignment::where('user_id', $userId)
            ->whereIn('status', ['active', 'pending']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        // Check for overlapping date ranges
        $query->where(function ($q) use ($startDate, $endDate) {
            $q->where(function ($q2) use ($startDate, $endDate) {
                // New assignment starts during existing assignment
                $q2->where('start_date', '<=', $startDate);

                if ($endDate) {
                    $q2->where(function ($q3) use ($startDate) {
                        $q3->whereNull('end_date')
                            ->orWhere('end_date', '>=', $startDate);
                    });
                } else {
                    $q2->whereNull('end_date');
                }
            })->orWhere(function ($q2) use ($endDate) {
                // New assignment ends during existing assignment
                if ($endDate) {
                    $q2->where('start_date', '<=', $endDate)
                        ->where(function ($q3) use ($endDate) {
                            $q3->whereNull('end_date')
                                ->orWhere('end_date', '>=', $endDate);
                        });
                }
            })->orWhere(function ($q2) use ($startDate, $endDate) {
                // New assignment encompasses existing assignment
                if ($endDate) {
                    $q2->where('start_date', '>=', $startDate)
                        ->where('start_date', '<=', $endDate);
                }
            });
        });

        return $query->get();
    }

    /**
     * Get station coverage matrix.
     */
    public function getStationCoverage(Request $request): Response
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $stations = Station::where('is_active', true)
            ->with(['region', 'teamAssignments' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'active')
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                $q2->where('start_date', '<=', $startDate)
                                    ->where(function ($q3) use ($endDate) {
                                        $q3->where('end_date', '>=', $endDate)
                                            ->orWhereNull('end_date');
                                    });
                            });
                    })
                    ->with('user');
            }])
            ->get()
            ->map(fn ($station) => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'region' => $station->region->name,
                'expected_headcount' => $station->expected_headcount,
                'coverage_status' => $station->teamAssignments->count() > 0 ? 'covered' : 'uncovered',
                'assigned_teams' => $station->teamAssignments->map(fn ($assignment) => [
                    'id' => $assignment->id,
                    'user_name' => $assignment->user->name,
                    'start_date' => $assignment->start_date->format('Y-m-d'),
                    'end_date' => $assignment->end_date?->format('Y-m-d'),
                ]),
            ]);

        // Group by region
        $coverage = $stations->groupBy('region')->map(function ($regionStations, $region) {
            return [
                'region' => $region,
                'total_stations' => $regionStations->count(),
                'covered_stations' => $regionStations->where('coverage_status', 'covered')->count(),
                'uncovered_stations' => $regionStations->where('coverage_status', 'uncovered')->count(),
                'coverage_percentage' => $regionStations->count() > 0
                    ? round(($regionStations->where('coverage_status', 'covered')->count() / $regionStations->count()) * 100, 2)
                    : 0,
                'stations' => $regionStations->values(),
            ];
        })->values();

        return Inertia::render('assignments/Coverage', [
            'coverage' => $coverage,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Reassign team to a different station.
     */
    public function reassign(Request $request, TeamAssignment $assignment): RedirectResponse
    {
        if (! $assignment->isActive()) {
            return back()->with('error', 'Only active assignments can be reassigned.');
        }

        $validated = $request->validate([
            'new_station_id' => 'required|exists:stations,id|different:station_id',
            'reason' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($assignment, $validated) {
            // End current assignment
            $assignment->update([
                'status' => 'completed',
                'end_date' => now(),
            ]);

            // Create new assignment
            TeamAssignment::create([
                'user_id' => $assignment->user_id,
                'station_id' => $validated['new_station_id'],
                'headcount_session_id' => $assignment->headcount_session_id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => $assignment->end_date,
                'status' => 'active',
                'assigned_by' => auth()->id(),
            ]);
        });

        return redirect()->route('assignments.index')
            ->with('success', 'Team reassigned successfully.');
    }
}
