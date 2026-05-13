<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UnitsController extends Controller
{
    // ── Create ────────────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        return Inertia::render('Units/Form', [
            'amenities'         => Amenity::orderBy('name')->get(['id', 'name', 'icon']),
            'properties'        => Property::orderBy('name')->get(['id', 'name']),
            'defaultPropertyId' => $request->integer('property_id') ?: null,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id'    => ['required', 'integer', 'exists:properties,id'],
            'unit_number'    => [
                'required',
                'string',
                'max:20',
                // Unit number must be unique within the same property
                Rule::unique('units')->where('property_id', $request->input('property_id')),
            ],
            'type'           => ['required', Rule::in(['studio', '1BR', '2BR', '3BR', 'penthouse', 'commercial'])],
            'floor_number'   => ['required', 'integer', 'min:0', 'max:200'],
            'size_sqm'       => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'rent_price'     => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'status'         => ['required', Rule::in(['vacant', 'occupied', 'maintenance', 'reserved'])],
            'description'    => ['nullable', 'string', 'max:2000'],
            'amenity_ids'    => ['nullable', 'array'],
            'amenity_ids.*'  => ['integer', 'exists:amenities,id'],
        ]);

        $unit = Unit::create($data);
        $unit->amenities()->sync($request->input('amenity_ids', []));

        // Update property occupancy count
        $this->refreshPropertyOccupancy($unit->property_id);

        return redirect()->route('properties.show', $unit->property_id)
            ->with('success', "Unit {$unit->unit_number} created.");
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Unit $unit)
    {
        $unit->load([
            'property:id,name',
            'amenities',
            'activeLease.tenant',
            'leases' => fn($q) => $q->with('tenant:id,name')->latest()->limit(10),
            'maintenanceRequests' => fn($q) => $q->latest()->limit(20),
        ]);

        return Inertia::render('Units/Show', [
            'unit' => [
                'id'             => $unit->id,
                'unit_number'    => $unit->unit_number,
                'type'           => $unit->type,
                'floor_number'   => $unit->floor_number,
                'size_sqm'       => $unit->size_sqm,
                'rent_price'     => $unit->rent_price,
                'deposit_amount' => $unit->deposit_amount,
                'status'         => $unit->status,
                'description'    => $unit->description,
                'property'       => $unit->property,
                'amenities'      => $unit->amenities,

                'active_lease'   => $unit->activeLease ? [
                    'id'             => $unit->activeLease->id,
                    'start_date'     => $unit->activeLease->start_date,
                    'end_date'       => $unit->activeLease->end_date,
                    'rent_price'     => $unit->activeLease->rent_price,
                    'deposit_amount' => $unit->activeLease->deposit_amount,
                    'status'         => $unit->activeLease->status,
                    'tenant'         => [
                        'id'    => $unit->activeLease->tenant->id,
                        'name'  => $unit->activeLease->tenant->name,
                        'email' => $unit->activeLease->tenant->email,
                        'phone' => $unit->activeLease->tenant->phone,
                    ],
                ] : null,

                'lease_history' => $unit->leases
                    ->where('id', '!=', $unit->activeLease?->id)
                    ->map(fn($l) => [
                        'id'         => $l->id,
                        'start_date' => $l->start_date,
                        'end_date'   => $l->end_date,
                        'rent_price' => $l->rent_price,
                        'tenant'     => $l->tenant ? ['id' => $l->tenant->id, 'name' => $l->tenant->name] : null,
                    ])->values(),

                'maintenance_requests' => $unit->maintenanceRequests->map(fn($r) => [
                    'id'         => $r->id,
                    'title'      => $r->title,
                    'status'     => $r->status,
                    'priority'   => $r->priority,
                    'created_at' => $r->created_at->toISOString(),
                ]),
            ],
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(Unit $unit)
    {
        $unit->load('amenities');

        return Inertia::render('Units/Form', [
            'unit' => [
                'id'             => $unit->id,
                'property_id'    => $unit->property_id,
                'unit_number'    => $unit->unit_number,
                'type'           => $unit->type,
                'floor_number'   => $unit->floor_number,
                'size_sqm'       => $unit->size_sqm,
                'rent_price'     => $unit->rent_price,
                'deposit_amount' => $unit->deposit_amount,
                'status'         => $unit->status,
                'description'    => $unit->description,
                'amenity_ids'    => $unit->amenities->pluck('id')->toArray(),
            ],
            'amenities'  => Amenity::orderBy('name')->get(['id', 'name', 'icon']),
            'properties' => Property::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'property_id'    => ['required', 'integer', 'exists:properties,id'],
            'unit_number'    => [
                'required',
                'string',
                'max:20',
                Rule::unique('units')
                    ->where('property_id', $request->input('property_id'))
                    ->ignore($unit->id),
            ],
            'type'           => ['required', Rule::in(['studio', '1BR', '2BR', '3BR', 'penthouse', 'commercial'])],
            'floor_number'   => ['required', 'integer', 'min:0', 'max:200'],
            'size_sqm'       => ['nullable', 'numeric', 'min:0'],
            'rent_price'     => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'status'         => ['required', Rule::in(['vacant', 'occupied', 'maintenance', 'reserved'])],
            'description'    => ['nullable', 'string', 'max:2000'],
            'amenity_ids'    => ['nullable', 'array'],
            'amenity_ids.*'  => ['integer', 'exists:amenities,id'],
        ]);

        // Prevent marking as vacant if there's an active lease
        if ($data['status'] === 'vacant' && $unit->activeLease) {
            return back()->withErrors([
                'status' => 'Cannot mark as vacant while an active lease exists. Terminate the lease first.',
            ]);
        }

        $oldPropertyId = $unit->property_id;
        $unit->update($data);
        $unit->amenities()->sync($request->input('amenity_ids', []));

        // Refresh occupancy on old and new property (in case property changed)
        $this->refreshPropertyOccupancy($oldPropertyId);
        if ($oldPropertyId !== $unit->property_id) {
            $this->refreshPropertyOccupancy($unit->property_id);
        }

        return redirect()->route('units.show', $unit)
            ->with('success', "Unit {$unit->unit_number} updated.");
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    public function destroy(Unit $unit)
    {
        // Block if active lease exists
        if ($unit->activeLease) {
            return back()->with('error', 'Cannot delete a unit with an active lease.');
        }

        // Block if not vacant
        if ($unit->status !== 'vacant') {
            return back()->with('error', 'Only vacant units can be deleted.');
        }

        $propertyId = $unit->property_id;
        $unitNumber  = $unit->unit_number;

        $unit->delete(); // SoftDeletes

        $this->refreshPropertyOccupancy($propertyId);

        return redirect()->route('properties.show', $propertyId)
            ->with('success', "Unit {$unitNumber} deleted.");
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Recalculate and persist the occupied_units count on the parent property.
     * Call this any time a unit's status changes.
     */
    private function refreshPropertyOccupancy(int $propertyId): void
    {
        $property = Property::find($propertyId);

        if (! $property) return;

        $property->update([
            'occupied_units' => $property->units()->where('status', 'occupied')->count(),
        ]);
    }
}
