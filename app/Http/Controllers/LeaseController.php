<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LeaseController extends Controller
{
    // ── Create ────────────────────────────────────────────────────────────────
    /**
     * Show form to create a new lease.
     * Can be pre-scoped to a unit: ?unit_id=123
     */
    public function create(Request $request)
    {
        $unitId = $request->integer('unit_id');
        $unit = $unitId ? Unit::findOrFail($unitId)->load('property') : null;

        // Only vacant units can have new leases
        if ($unit && $unit->status !== 'vacant') {
            return back()->with('error', 'Only vacant units can have new leases.');
        }

        return Inertia::render('Leases/Form', [
            'lease'   => null,
            'unit'    => $unit ? [
                'id'          => $unit->id,
                'unit_number' => $unit->unit_number,
                'property_id' => $unit->property_id,
            ] : null,
            'units'   => Unit::where('status', 'vacant')->orderBy('unit_number')->get(['id', 'unit_number', 'property_id']),
            'tenants' => Tenant::orderBy('name')->get(['id', 'name', 'email', 'phone']),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_id'         => ['required', 'integer', 'exists:units,id'],
            'tenant_id'       => ['required', 'integer', 'exists:tenants,id'],
            'start_date'      => ['required', 'date', 'date_format:Y-m-d'],
            'end_date'        => ['required', 'date', 'date_format:Y-m-d', 'after:start_date'],
            'rent_price'      => ['required', 'numeric', 'min:0'],
            'deposit_amount'  => ['required', 'numeric', 'min:0'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ]);

        $unit = Unit::findOrFail($data['unit_id']);

        // Verify unit is vacant
        if ($unit->status !== 'vacant') {
            return back()->withErrors(['unit_id' => 'This unit is not vacant.']);
        }

        // Verify unit doesn't already have an active lease
        if ($unit->activeLease) {
            return back()->withErrors(['unit_id' => 'This unit already has an active lease.']);
        }

        $data['created_by'] = Auth::id();
        $data['status']     = 'active';

        $lease = Lease::create($data);

        // Mark unit as occupied
        $unit->update(['status' => 'occupied']);

        return redirect()->route('leases.show', $lease)
            ->with('success', 'Lease created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Lease $lease)
    {
        $lease->load([
            'unit.property',
            'tenant',
            'payments' => fn($q) => $q->latest('due_date'),
            'maintenanceRequests',
        ]);

        return Inertia::render('Leases/Show', [
            'lease' => [
                'id'                  => $lease->id,
                'unit'                => [
                    'id'          => $lease->unit->id,
                    'unit_number' => $lease->unit->unit_number,
                    'property'    => [
                        'id'   => $lease->unit->property->id,
                        'name' => $lease->unit->property->name,
                    ],
                ],
                'tenant'              => [
                    'id'                      => $lease->tenant->id,
                    'name'                    => $lease->tenant->name,
                    'email'                   => $lease->tenant->email,
                    'phone'                   => $lease->tenant->phone,
                    'emergency_contact_name' => $lease->tenant->emergency_contact_name,
                    'emergency_contact_phone' => $lease->tenant->emergency_contact_phone,
                    'address'                 => $lease->tenant->full_address,
                ],
                'start_date'          => $lease->start_date->toISOString(),
                'end_date'            => $lease->end_date->toISOString(),
                'rent_price'          => $lease->rent_price,
                'deposit_amount'      => $lease->deposit_amount,
                'status'              => $lease->status,
                'terminated_at'       => $lease->terminated_at?->toISOString(),
                'termination_reason'  => $lease->termination_reason,
                'notes'               => $lease->notes,

                'days_remaining'      => $lease->days_remaining,
                'is_active'           => $lease->is_active,
                'is_expiring'         => $lease->is_expiring,
                'is_expired'          => $lease->is_expired,
                'duration_months'     => $lease->duration_months,
                'total_rent'          => $lease->total_rent,
                'total_paid'          => $lease->total_paid,
                'balance'             => $lease->balance,

                'payments'            => $lease->payments->map(fn($p) => [
                    'id'               => $p->id,
                    'amount'           => $p->amount,
                    'status'           => $p->status,
                    'due_date'         => $p->due_date->toISOString(),
                    'payment_date'     => $p->payment_date?->toISOString(),
                    'payment_method'   => $p->payment_method,
                    'reference_number' => $p->reference_number,
                    'is_overdue'       => $p->is_overdue,
                ]),

                'maintenance_requests' => $lease->maintenanceRequests->map(fn($m) => [
                    'id'    => $m->id,
                    'title' => $m->title,
                    'status' => $m->status,
                ]),
            ],
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(Lease $lease)
    {
        $lease->load(['unit.property', 'tenant']);

        return Inertia::render('Leases/Form', [
            'lease' => [
                'id'             => $lease->id,
                'unit_id'        => $lease->unit_id,
                'tenant_id'      => $lease->tenant_id,
                'start_date'     => $lease->start_date->format('Y-m-d'),
                'end_date'       => $lease->end_date->format('Y-m-d'),
                'rent_price'     => $lease->rent_price,
                'deposit_amount' => $lease->deposit_amount,
                'notes'          => $lease->notes,
            ],
            'unit'  => [
                'id'          => $lease->unit->id,
                'unit_number' => $lease->unit->unit_number,
            ],
            'units'   => Unit::orderBy('unit_number')->get(['id', 'unit_number', 'property_id']),
            'tenants' => Tenant::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, Lease $lease)
    {
        // Can't edit if terminated
        if ($lease->status === 'terminated') {
            return back()->with('error', 'Cannot edit a terminated lease.');
        }

        $data = $request->validate([
            'end_date'       => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:' . $lease->start_date->format('Y-m-d')],
            'rent_price'     => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        $lease->update($data);

        return redirect()->route('leases.show', $lease)
            ->with('success', 'Lease updated.');
    }

    // ── Renew ─────────────────────────────────────────────────────────────────
    /**
     * Renew a lease for another period.
     * POST /leases/{lease}/renew
     */
    public function renew(Request $request, Lease $lease)
    {
        // Can only renew if the current lease is still active or about to expire
        if ($lease->status === 'terminated') {
            return back()->with('error', 'Cannot renew a terminated lease.');
        }

        $data = $request->validate([
            'months'          => ['required', 'integer', 'min:1', 'max:60'],
            'new_rent_price'  => ['required', 'numeric', 'min:0'],
        ]);

        $newLease = $lease->renew(
            monthsToAdd: $data['months'],
            newRentPrice: $data['new_rent_price']
        );

        return redirect()->route('leases.show', $newLease)
            ->with('success', 'Lease renewed successfully.');
    }

    // ── Terminate ─────────────────────────────────────────────────────────────
    /**
     * Terminate a lease early.
     * POST /leases/{lease}/terminate
     */
    public function terminate(Request $request, Lease $lease)
    {
        if ($lease->status === 'terminated') {
            return back()->with('error', 'Lease is already terminated.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $lease->terminate($request->input('reason'));

        return redirect()->route('leases.show', $lease)
            ->with('success', 'Lease terminated. Unit marked as vacant.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    /**
     * Hard delete a lease (rarely used, only for data cleanup).
     */
    public function destroy(Lease $lease)
    {
        if ($lease->status !== 'terminated') {
            return back()->with('error', 'Only terminated leases can be deleted.');
        }

        $lease->forceDelete();

        return redirect()->route('properties.index')
            ->with('success', 'Lease deleted.');
    }
}
