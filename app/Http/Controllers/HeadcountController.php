<?php

namespace App\Http\Controllers;

use App\Enums\HeadcountSessionStatus;
use App\Models\Discrepancy;
use App\Models\HeadcountSession;
use App\Models\HeadcountVerification;
use App\Models\Staff;
use App\Models\Station;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HeadcountController extends Controller
{
    /**
     * Display list of all headcount sessions with statistics.
     */
    public function index(Request $request): Response
    {
        $query = HeadcountSession::with('createdBy')->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        $sessions = $query->paginate(15)->through(fn ($session) => [
            'id' => $session->id,
            'session_name' => $session->session_name,
            'description' => $session->description,
            'status' => $session->status,
            'start_date' => $session->start_date->format('Y-m-d'),
            'end_date' => $session->end_date?->format('Y-m-d'),
            'completion_percentage' => $session->getCompletionPercentage(),
            'verification_stats' => $session->getVerificationStats(),
            'created_at' => $session->created_at->format('Y-m-d H:i'),
            'created_by' => [
                'id' => $session->createdBy->id,
                'name' => $session->createdBy->name,
            ],
            'can_start' => $session->status === HeadcountSessionStatus::Planned,
            'can_end' => $session->status === HeadcountSessionStatus::InProgress,
        ]);

        // Overall statistics
        $statistics = [
            'total_sessions' => HeadcountSession::count(),
            'active_sessions' => HeadcountSession::where('status', HeadcountSessionStatus::InProgress)->count(),
            'total_verifications' => HeadcountVerification::whereHas('headcountSession')->count(),
            'ghost_employees_detected' => Discrepancy::where('discrepancy_type', 'ghost_employee')->count(),
        ];

        return Inertia::render('headcount/Index', [
            'sessions' => $sessions,
            'statistics' => $statistics,
            'filters' => $request->only(['status', 'created_by', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show create session form.
     */
    public function create(): Response
    {
        $stations = Station::where('is_active', true)
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

        return Inertia::render('headcount/Create', [
            'stations' => $stations,
        ]);
    }

    /**
     * Store a new headcount session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $session = HeadcountSession::create([
            ...$validated,
            'status' => HeadcountSessionStatus::Planned,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('headcount.show', $session)
            ->with('success', 'Headcount session created successfully.');
    }

    /**
     * Display headcount session details with verifications.
     */
    public function show(HeadcountSession $session): Response
    {
        $session->load(['createdBy', 'verifications.staff.unit.department', 'verifications.verifiedBy']);

        // Get verification statistics
        $stats = $session->getVerificationStats();

        // Get recent verifications (last 20)
        $recentVerifications = $session->verifications()
            ->with(['staff', 'verifiedBy'])
            ->latest('verified_at')
            ->limit(20)
            ->get()
            ->map(fn ($verification) => [
                'id' => $verification->id,
                'staff' => [
                    'id' => $verification->staff->id,
                    'staff_number' => $verification->staff->staff_number,
                    'full_name' => $verification->staff->full_name,
                    'job_title' => $verification->staff->jobTitle?->title,
                    'department' => $verification->staff->unit?->department?->name,
                ],
                'verification_status' => $verification->verification_status,
                'location' => $verification->location,
                'verified_at' => $verification->verified_at->format('Y-m-d H:i'),
                'verified_by' => [
                    'id' => $verification->verifiedBy->id,
                    'name' => $verification->verifiedBy->name,
                ],
                'has_photos' => $verification->photos()->count() > 0,
                'notes_count' => $verification->notes()->count(),
            ]);

        return Inertia::render('headcount/Show', [
            'session' => [
                'id' => $session->id,
                'session_name' => $session->session_name,
                'description' => $session->description,
                'status' => $session->status,
                'start_date' => $session->start_date->format('Y-m-d'),
                'end_date' => $session->end_date?->format('Y-m-d'),
                'completion_percentage' => $session->getCompletionPercentage(),
                'created_at' => $session->created_at->format('Y-m-d H:i'),
                'created_by' => [
                    'id' => $session->createdBy->id,
                    'name' => $session->createdBy->name,
                ],
                'can_start' => $session->status === HeadcountSessionStatus::Planned,
                'can_end' => $session->status === HeadcountSessionStatus::InProgress,
                'is_active' => $session->isActive(),
            ],
            'statistics' => $stats,
            'verifications' => $recentVerifications,
        ]);
    }

    /**
     * Start a headcount session.
     */
    public function startSession(HeadcountSession $session): RedirectResponse
    {
        if ($session->status !== HeadcountSessionStatus::Planned) {
            return back()->with('error', 'Only pending sessions can be started.');
        }

        // Check for other active sessions
        $activeSession = HeadcountSession::where('status', HeadcountSessionStatus::InProgress)
            ->where('id', '!=', $session->id)
            ->exists();

        if ($activeSession) {
            return back()->with('error', 'Another session is already in progress. Please complete it first.');
        }

        $session->start();

        return back()->with('success', 'Headcount session started successfully.');
    }

    /**
     * End a headcount session.
     */
    public function endSession(HeadcountSession $session): RedirectResponse
    {
        if (! $session->isActive()) {
            return back()->with('error', 'Only active sessions can be ended.');
        }

        $session->complete();

        return redirect()->route('headcount.report', $session)
            ->with('success', 'Headcount session completed successfully.');
    }

    /**
     * Pause a headcount session.
     */
    public function pauseSession(HeadcountSession $session): RedirectResponse
    {
        if (! $session->isActive()) {
            return back()->with('error', 'Only active sessions can be paused.');
        }

        $session->update(['status' => HeadcountSessionStatus::Paused]);

        return back()->with('success', 'Headcount session paused.');
    }

    /**
     * Show verification form for capturing staff attendance.
     */
    public function verificationForm(HeadcountSession $session, Request $request): Response
    {
        if (! $session->isActive()) {
            abort(403, 'This session is not active.');
        }

        // Get stations with staff counts
        $stations = Station::where('is_active', true)
            ->withCount('staff')
            ->orderBy('name')
            ->get()
            ->map(fn ($station) => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'city' => $station->city,
                'staff_count' => $station->staff_count,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'gps_boundary' => $station->gps_boundary,
            ]);

        // Get staff list for selected station
        $staff = [];
        if ($request->filled('station_id')) {
            $stationId = $request->station_id;

            $staff = Staff::where('station_id', $stationId)
                ->where('is_active', true)
                ->with(['unit.department', 'jobTitle'])
                ->get()
                ->map(fn ($staffMember) => [
                    'id' => $staffMember->id,
                    'staff_number' => $staffMember->staff_number,
                    'full_name' => $staffMember->full_name,
                    'job_title' => $staffMember->jobTitle?->title,
                    'department' => $staffMember->unit?->department?->name,
                    'unit' => $staffMember->unit?->name,
                    'is_verified' => $session->verifications()
                        ->where('staff_id', $staffMember->id)
                        ->exists(),
                ]);
        }

        return Inertia::render('headcount/Verify', [
            'session' => [
                'id' => $session->id,
                'session_name' => $session->session_name,
            ],
            'stations' => $stations,
            'staff' => $staff,
            'selected_station_id' => $request->filled('station_id') ? (int) $request->station_id : null,
        ]);
    }

    /**
     * Capture staff verification with photo and GPS.
     */
    public function captureVerification(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'headcount_session_id' => 'required|exists:headcount_sessions,id',
            'staff_id' => 'required|exists:staff,id',
            'station_id' => 'required|exists:stations,id',
            'verification_status' => 'required|in:present,absent,on_leave,ghost',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'photo' => 'required|image|max:5120', // 5MB max
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Check for duplicate verification
        $exists = HeadcountVerification::where('headcount_session_id', $validated['headcount_session_id'])
            ->where('staff_id', $validated['staff_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'This staff member has already been verified in this session.');
        }

        // Validate GPS if provided
        if ($validated['latitude'] && $validated['longitude']) {
            $station = Station::find($validated['station_id']);

            if (! $station->validateGPSLocation($validated['latitude'], $validated['longitude'])) {
                return back()->with('error', 'GPS location is outside the station boundary.');
            }
        }

        DB::transaction(function () use ($validated, $request) {
            // Create verification record
            $verification = HeadcountVerification::create([
                'headcount_session_id' => $validated['headcount_session_id'],
                'staff_id' => $validated['staff_id'],
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_status' => $validated['verification_status'],
                'location' => $validated['latitude'] && $validated['longitude']
                    ? json_encode([
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                        'station_id' => $validated['station_id'],
                    ])
                    : null,
                'remarks' => $validated['remarks'],
            ]);

            // Store photo
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $path = $photo->store('verification-photos', 'public');

                $verification->addPhoto($path, 'verification', 'Verification photo');
            }

            // Auto-detect ghost employee
            if ($validated['verification_status'] === 'ghost') {
                $staff = Staff::find($validated['staff_id']);

                // Create discrepancy record
                Discrepancy::create([
                    'staff_id' => $staff->id,
                    'discrepancy_type' => 'ghost_employee',
                    'severity' => 'critical',
                    'description' => "Staff member flagged as ghost employee during headcount verification. Remarks: {$validated['remarks']}",
                    'status' => 'open',
                    'detected_by' => auth()->id(),
                    'detected_at' => now(),
                ]);

                // Flag staff record
                $staff->flagAsGhost("Detected during headcount session {$validated['headcount_session_id']}");
            }
        });

        return back()->with('success', 'Verification captured successfully.');
    }

    /**
     * Bulk verify multiple staff members.
     */
    public function bulkVerify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'headcount_session_id' => 'required|exists:headcount_sessions,id',
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'required|exists:staff,id',
            'verification_status' => 'required|in:present,absent,on_leave',
            'station_id' => 'required|exists:stations,id',
        ]);

        $session = HeadcountSession::find($validated['headcount_session_id']);

        if (! $session->isActive()) {
            return back()->with('error', 'This session is not active.');
        }

        $count = 0;
        foreach ($validated['staff_ids'] as $staffId) {
            // Check for existing verification
            $exists = HeadcountVerification::where('headcount_session_id', $validated['headcount_session_id'])
                ->where('staff_id', $staffId)
                ->exists();

            if (! $exists) {
                HeadcountVerification::create([
                    'headcount_session_id' => $validated['headcount_session_id'],
                    'staff_id' => $staffId,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'verification_status' => $validated['verification_status'],
                    'location' => json_encode(['station_id' => $validated['station_id']]),
                ]);

                $count++;
            }
        }

        return back()->with('success', "{$count} staff members verified successfully.");
    }

    /**
     * Get verification history for a specific staff member.
     */
    public function verificationHistory(Staff $staff): Response
    {
        $verifications = $staff->headcountVerifications()
            ->with(['headcountSession', 'verifiedBy', 'photos', 'notes'])
            ->latest('verified_at')
            ->get()
            ->map(fn ($verification) => [
                'id' => $verification->id,
                'session_name' => $verification->headcountSession->session_name,
                'verification_status' => $verification->verification_status,
                'verified_at' => $verification->verified_at->format('Y-m-d H:i'),
                'verified_by' => [
                    'id' => $verification->verifiedBy->id,
                    'name' => $verification->verifiedBy->name,
                ],
                'location' => $verification->location,
                'remarks' => $verification->remarks,
                'photos' => $verification->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'photo_path' => Storage::url($photo->photo_path),
                    'caption' => $photo->caption,
                ]),
                'notes' => $verification->notes->map(fn ($note) => [
                    'id' => $note->id,
                    'note_content' => $note->note_content,
                    'created_at' => $note->created_at->format('Y-m-d H:i'),
                ]),
            ]);

        return Inertia::render('headcount/History', [
            'staff' => [
                'id' => $staff->id,
                'staff_number' => $staff->staff_number,
                'full_name' => $staff->full_name,
                'job_title' => $staff->jobTitle?->title,
                'department' => $staff->unit?->department?->name,
            ],
            'verifications' => $verifications,
        ]);
    }

    /**
     * Generate session report with statistics and discrepancies.
     */
    public function sessionReport(HeadcountSession $session): Response
    {
        $session->load(['createdBy', 'verifications.staff']);

        $stats = $session->getVerificationStats();

        // Get discrepancies detected during this session
        $discrepancies = Discrepancy::whereHas('staff.headcountVerifications', function ($query) use ($session) {
            $query->where('headcount_session_id', $session->id);
        })
            ->with(['staff', 'detectedBy'])
            ->get()
            ->map(fn ($discrepancy) => [
                'id' => $discrepancy->id,
                'staff' => [
                    'staff_number' => $discrepancy->staff->staff_number,
                    'full_name' => $discrepancy->staff->full_name,
                ],
                'discrepancy_type' => $discrepancy->getTypeLabel(),
                'severity' => $discrepancy->severity,
                'description' => $discrepancy->description,
                'status' => $discrepancy->status,
                'detected_by' => $discrepancy->detectedBy->name,
                'detected_at' => $discrepancy->detected_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('headcount/Report', [
            'session' => [
                'id' => $session->id,
                'session_name' => $session->session_name,
                'description' => $session->description,
                'status' => $session->status,
                'start_date' => $session->start_date->format('Y-m-d'),
                'end_date' => $session->end_date?->format('Y-m-d'),
                'completion_percentage' => $session->getCompletionPercentage(),
                'created_by' => [
                    'name' => $session->createdBy->name,
                ],
            ],
            'statistics' => $stats,
            'discrepancies' => $discrepancies,
        ]);
    }
}
