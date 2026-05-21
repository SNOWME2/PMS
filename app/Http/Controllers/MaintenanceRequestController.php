<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MaintenanceRequestController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────
    /**
     * List maintenance requests with filters
     */
    public function index(Request $request)
    {
        $query = MaintenanceRequest::query()
            ->with(['unit.property', 'tenant', 'assignedTo', 'updates'])
            ->latest('created_at');

        // Filter by status
        if ($status = $request->input('status')) {
            match ($status) {
                'active'    => $query->active(),
                'open'      => $query->open(),
                'assigned'  => $query->assigned(),
                'in-progress' => $query->inProgress(),
                'completed' => $query->completed(),
                'overdue'   => $query->overdue(),
                default     => null,
            };
        }

        // Filter by priority
        if ($priority = $request->input('priority')) {
            $query->priority($priority);
        }

        // Filter by category
        if ($category = $request->input('category')) {
            $query->category($category);
        }

        // Filter by assigned staff
        if ($assignedTo = $request->integer('assigned_to')) {
            $query->assignedTo($assignedTo);
        }

        // Filter by property
        if ($propertyId = $request->integer('property_id')) {
            $query->forProperty($propertyId);
        }

        // Search by title or description
        if ($search = $request->input('search')) {
            $query->where(
                fn($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            );
        }

        $requests = $query->get()->map(fn($r) => $this->formatRequest($r));
        $staff = User::where('role', 'staff')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Maintenance/Index', [
            'requests' => $requests,
            'staff'    => $staff,
            'filters'  => $request->only(['status', 'priority', 'category', 'assigned_to', 'property_id', 'search']),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────
    /**
     * Show form to create a new maintenance request
     */
    public function create(Request $request)
    {
        $unitId = $request->integer('unit_id');
        $unit = $unitId ? Unit::findOrFail($unitId)->load('property') : null;

        return Inertia::render('Maintenance/Form', [
            'request' => null,
            'unit'    => $unit ? [
                'id'          => $unit->id,
                'unit_number' => $unit->unit_number,
                'property_id' => $unit->property_id,
            ] : null,
            'units' => Unit::with('property:id,name')
                ->orderBy('unit_number')
                ->get(['id', 'unit_number', 'property_id']),
            'staff' => User::where('role', 'staff')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_id'         => ['required', 'integer', 'exists:units,id'],
            'tenant_id'       => ['nullable', 'integer', 'exists:tenants,id'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string', 'max:2000'],
            'category'        => ['required', Rule::in(['plumbing', 'electrical', 'hvac', 'appliance', 'paint', 'cleaning', 'structural', 'other'])],
            'priority'        => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'estimated_cost'  => ['nullable', 'numeric', 'min:0'],
            'assigned_to'     => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $data['created_by'] = Auth::id();
        $data['status']     = $data['assigned_to'] ? 'assigned' : 'open';
        $data['assigned_at'] = $data['assigned_to'] ? now() : null;

        $req = MaintenanceRequest::create($data);

        if ($data['assigned_to']) {
            $req->logUpdate('assigned', "Assigned to {$req->assignedTo->name}");
        }

        return redirect()->route('maintenance.show', $req)
            ->with('success', 'Maintenance request created.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->load([
            'unit.property',
            'tenant',
            'createdBy',
            'assignedTo',
            'updates.createdBy',
        ]);

        return Inertia::render('Maintenance/Show', [
            'request' => $this->formatRequest($maintenanceRequest),
            'updates' => $maintenanceRequest->updates->map(fn($u) => [
                'id'          => $u->id,
                'type'        => $u->type,
                'title'       => $u->title,
                'description' => $u->description,
                'created_by'  => $u->createdBy ? ['id' => $u->createdBy->id, 'name' => $u->createdBy->name] : null,
                'created_at'  => $u->created_at->toISOString(),
            ]),
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(MaintenanceRequest $maintenanceRequest)
    {
        $maintenanceRequest->load(['unit.property', 'assignedTo']);

        return Inertia::render('Maintenance/Form', [
            'request' => [
                'id'             => $maintenanceRequest->id,
                'unit_id'        => $maintenanceRequest->unit_id,
                'title'          => $maintenanceRequest->title,
                'description'    => $maintenanceRequest->description,
                'category'       => $maintenanceRequest->category,
                'priority'       => $maintenanceRequest->priority,
                'estimated_cost' => $maintenanceRequest->estimated_cost,
                'assigned_to'    => $maintenanceRequest->assigned_to,
            ],
            'unit'  => [
                'id'          => $maintenanceRequest->unit->id,
                'unit_number' => $maintenanceRequest->unit->unit_number,
            ],
            'units' => Unit::with('property:id,name')
                ->orderBy('unit_number')
                ->get(['id', 'unit_number', 'property_id']),
            'staff' => User::where('role', 'staff')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        // Can't edit if completed or cancelled
        if (in_array($maintenanceRequest->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot edit a completed or cancelled request.');
        }

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['required', 'string', 'max:2000'],
            'category'        => ['required', Rule::in(['plumbing', 'electrical', 'hvac', 'appliance', 'paint', 'cleaning', 'structural', 'other'])],
            'priority'        => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'estimated_cost'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $maintenanceRequest->update($data);
        $maintenanceRequest->logUpdate('status_change', 'Request updated');

        return redirect()->route('maintenance.show', $maintenanceRequest)
            ->with('success', 'Request updated.');
    }

    // ── Assign ────────────────────────────────────────────────────────────────
    /**
     * Assign request to staff member
     * POST /maintenance/{id}/assign
     */
    public function assign(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($request->input('assigned_to'));
        $maintenanceRequest->assignTo($user, $request->input('notes'));

        return back()->with('success', "Assigned to {$user->name}");
    }

    // ── Start work ────────────────────────────────────────────────────────────
    /**
     * Mark as in-progress
     * POST /maintenance/{id}/start
     */
    public function start(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        if (! in_array($maintenanceRequest->status, ['assigned'])) {
            return back()->with('error', 'Only assigned requests can be started.');
        }

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $maintenanceRequest->markInProgress($request->input('notes'));

        return back()->with('success', 'Work started.');
    }

    // ── Complete ──────────────────────────────────────────────────────────────
    /**
     * Mark as completed
     * POST /maintenance/{id}/complete
     */
    public function complete(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        if (! in_array($maintenanceRequest->status, ['assigned', 'in-progress'])) {
            return back()->with('error', 'Cannot complete this request.');
        }

        $data = $request->validate([
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $maintenanceRequest->markCompleted($data['actual_cost'] ?? null, $data['notes']);

        return back()->with('success', 'Request marked as completed.');
    }

    // ── Cancel ────────────────────────────────────────────────────────────────
    /**
     * Cancel a request
     * POST /maintenance/{id}/cancel
     */
    public function cancel(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $maintenanceRequest->cancel($request->input('reason'));

        return back()->with('success', 'Request cancelled.');
    }

    // ── Upload photos ─────────────────────────────────────────────────────────
    /**
     * Upload photos (before or after)
     * POST /maintenance/{id}/photos
     */
    public function uploadPhotos(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $request->validate([
            'type'   => ['required', Rule::in(['before', 'after'])],
            'photos' => ['required', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $paths = [];
        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store("maintenance/{$maintenanceRequest->id}", 'public');
            $paths[] = $path;

            if ($request->input('type') === 'before') {
                $maintenanceRequest->addPhotoBefore($path);
            } else {
                $maintenanceRequest->addPhotoAfter($path);
            }
        }

        $maintenanceRequest->logUpdate('photo_added', "Photo(s) added ({$request->input('type')})");

        return back()->with('success', count($paths) . ' photo(s) uploaded.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    public function destroy(MaintenanceRequest $maintenanceRequest)
    {
        if (! in_array($maintenanceRequest->status, ['open', 'cancelled'])) {
            return back()->with('error', 'Only open or cancelled requests can be deleted.');
        }

        $maintenanceRequest->forceDelete();

        return redirect()->route('maintenance.index')
            ->with('success', 'Request deleted.');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function formatRequest(MaintenanceRequest $r): array
    {
        return [
            'id'                => $r->id,
            'title'             => $r->title,
            'description'       => $r->description,
            'category'          => $r->category,
            'category_label'    => MaintenanceRequest::categoryLabel($r->category),
            'priority'          => $r->priority,
            'priority_label'    => MaintenanceRequest::priorityLabel($r->priority),
            'status'            => $r->status,
            'status_label'      => MaintenanceRequest::statusLabel($r->status),

            'unit'              => [
                'id'          => $r->unit->id,
                'unit_number' => $r->unit->unit_number,
                'property'    => [
                    'id'   => $r->unit->property->id,
                    'name' => $r->unit->property->name,
                ],
            ],
            'tenant'            => $r->tenant ? [
                'id'   => $r->tenant->id,
                'name' => $r->tenant->name,
            ] : null,
            'assigned_to'       => $r->assignedTo ? [
                'id'   => $r->assignedTo->id,
                'name' => $r->assignedTo->name,
            ] : null,
            'created_by'        => [
                'id'   => $r->createdBy?->id,
                'name' => $r->createdBy?->name,
            ],

            'estimated_cost'    => $r->estimated_cost,
            'actual_cost'       => $r->actual_cost,
            'cost_overrun'      => $r->cost_overrun,

            'days_ago'          => $r->days_ago,
            'is_overdue'        => $r->is_overdue,
            'resolution_time'   => $r->resolution_time,
            'time_in_progress'  => $r->time_in_progress,
            'has_photos'        => $r->has_photos,

            'notes'             => $r->notes,
            'internal_notes'    => $r->internal_notes,
            'photos_before'     => $r->photos_before ?? [],
            'photos_after'      => $r->photos_after ?? [],

            'created_at'        => $r->created_at->toISOString(),
            'assigned_at'       => $r->assigned_at?->toISOString(),
            'started_at'        => $r->started_at?->toISOString(),
            'completed_at'      => $r->completed_at?->toISOString(),
        ];
    }
}
