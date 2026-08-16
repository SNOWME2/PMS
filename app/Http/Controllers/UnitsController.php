<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UnitsController extends Controller
{
    private const INDEX_CACHE_TTL = 60 * 60 * 6; // 6 hours
    private const SHOW_CACHE_TTL  = 60 * 60 * 6;  // 6 hours
    private const LIST_CACHE_TTL  = 60 * 60;      // 1 hour — for the small dropdown lists below

    // ── Index ─────────────────────────────────────────────────────────────────
    /**
     * List all units across all properties
     * GET /units
     */
    public function index(Request $request)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "units.index.v{$version}." . md5(json_encode([
            'search'      => $request->input('search'),
            'property_id' => $request->input('property_id'),
            'floor'       => $request->input('floor'),
            'status'      => $request->input('status'),
        ]));

        $units = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::INDEX_CACHE_TTL),
            function () use ($request) {
                $query = Unit::query()
                    ->with([
                        'property:id,name,address,city',
                        'activeLease.tenant:id,name',
                        // Cap and narrow the columns pulled per unit — the index page only
                        // needs enough to render a status badge, not full request records.
                        'maintenanceRequests' => fn($q) => $q
                            ->select('id', 'unit_id', 'status')
                            ->latest()
                            ->limit(5),
                    ])
                    ->orderBy('property_id')
                    ->orderBy('floor_number')
                    ->orderBy('unit_number');

                if ($request->filled('property_id')) {
                    $query->where('property_id', $request->integer('property_id'));
                }

                // NOTE: was `if ($floor = $request->integer('floor'))`, which treats
                // floor 0 (ground floor) as "no filter" since 0 is falsy in PHP.
                // filled() checks presence in the request instead of truthiness.
                if ($request->filled('floor')) {
                    $query->where('floor_number', $request->integer('floor'));
                }

                if ($status = $request->input('status')) {
                    $query->where('status', $status);
                }

                if ($search = $request->input('search')) {
                    $query->where(
                        fn($q) => $q
                            ->where('unit_number', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhereHas('property', fn($p) => $p->where('name', 'like', "%{$search}%"))
                    );
                }

                return $query->get()->map(fn($u) => [
                    'id'           => $u->id,
                    'unit_number'  => $u->unit_number,
                    'type'         => $u->type,
                    'floor_number' => $u->floor_number,
                    'size_sqm'     => $u->size_sqm,
                    'rent_price'   => $u->rent_price,
                    'status'       => $u->status,
                    'property'     => [
                        'id'      => $u->property->id,
                        'name'    => $u->property->name,
                        'address' => $u->property->address,
                        'city'    => $u->property->city,
                    ],
                    'active_lease' => $u->activeLease ? [
                        'id'       => $u->activeLease->id,
                        'end_date' => $u->activeLease->end_date->toISOString(),
                        'tenant'   => [
                            'id'   => $u->activeLease->tenant->id,
                            'name' => $u->activeLease->tenant->name,
                        ],
                    ] : null,
                    'maintenance_requests' => $u->maintenanceRequests->map(fn($m) => [
                        'id'     => $m->id,
                        'status' => $m->status,
                    ])->toArray(),
                ]);
            }
        );

        return Inertia::render('Units/Index', [
            'units'      => $units,
            'properties' => $this->cachedPropertiesList(),
            'filters'    => $request->only(['search', 'property_id', 'floor', 'status']),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        return Inertia::render('Units/Form', [
            'amenities'         => $this->cachedAmenitiesList(),
            'properties'        => $this->cachedPropertiesList(),
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

        $this->invalidateCache();

        return redirect()
            ->route('properties.show', $unit->property_id)
            ->with('success', "Unit {$unit->unit_number} created.");
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Unit $unit)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "units.show.v{$version}.{$unit->id}";

        $data = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::SHOW_CACHE_TTL),
            function () use ($unit) {
                $unit->load([
                    'property:id,name',
                    'amenities',
                    'activeLease.tenant',
                    'leases' => fn($q) => $q->with('tenant:id,name')->latest()->limit(10),
                    'maintenanceRequests' => fn($q) => $q->latest()->limit(20),
                ]);

                return [
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

                    'active_lease' => $unit->activeLease ? [
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
                ];
            }
        );

        return Inertia::render('Units/Show', ['unit' => $data]);
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
            'amenities'  => $this->cachedAmenitiesList(),
            'properties' => $this->cachedPropertiesList(),
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

        $unit->update($data);
        $unit->amenities()->sync($request->input('amenity_ids', []));

        $this->invalidateCache();

        return redirect()
            ->route('units.show', $unit)
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
        $unitNumber = $unit->unit_number;

        $unit->delete(); // Uses SoftDeletes

        $this->invalidateCache();

        return redirect()
            ->route('properties.show', $propertyId)
            ->with('success', "Unit {$unitNumber} deleted.");
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────
    private function cacheVersion(): int
    {
        return Cache::get('units.cache.version', 1);
    }

    /**
     * Bump both this controller's own cache version and Properties' — a unit
     * being created, edited, or deleted changes its parent property's
     * unit/occupancy counts, which PropertiesController's cached index/show
     * responses depend on.
     *
     * This replaces the old refreshPropertyOccupancy(), which wrote to a
     * `properties.occupied_units` column that PropertiesController never
     * selects or relies on — that controller computes occupancy fresh via
     * withCount() on every read, so persisting a separate stored count risked
     * either throwing (if the column doesn't exist) or silently drifting out
     * of sync with the real per-unit statuses (if it does). Bumping the
     * version just makes the next read recompute from source, which is both
     * simpler and can't go stale.
     */
    private function invalidateCache(): void
    {
        $version = $this->cacheVersion();
        Cache::put('units.cache.version', $version + 1, now()->addDays(30));

        $propVersion = Cache::get('properties.cache.version', 1);
        Cache::put('properties.cache.version', $propVersion + 1, now()->addDays(30));
    }

    private function cachedPropertiesList()
    {
        $propVersion = Cache::get('properties.cache.version', 1);

        return Cache::remember(
            "properties.list.v{$propVersion}",
            now()->addSeconds(self::LIST_CACHE_TTL),
            fn() => Property::orderBy('name')->get(['id', 'name'])
        );
    }

    private function cachedAmenitiesList()
    {
        $amenityVersion = Cache::get('amenities.cache.version', 1);

        return Cache::remember(
            "amenities.list.v{$amenityVersion}",
            now()->addSeconds(self::LIST_CACHE_TTL),
            fn() => Amenity::orderBy('name')->get(['id', 'name', 'icon'])
        );
    }
}
