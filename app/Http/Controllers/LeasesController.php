<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Rules\ValidatorParamsRule;

class LeasesController extends Controller
{
    protected array $nullableString;
    protected array $requiredString;

    private const INDEX_CACHE_TTL = 60 * 60 * 6; // 6 hours
    private const SHOW_CACHE_TTL  = 60 * 60 * 6; // 6 hours

    public function __construct()
    {
        $this->nullableString = ValidatorParamsRule::nullableString();
        $this->requiredString = ValidatorParamsRule::requiredString();
    }

    // ── Index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "leases.index.v{$version}." . md5(json_encode([
            'search'      => $request->input('search'),
            'status'      => $request->input('status'), // active | expired | terminated | expiring
            'property_id' => $request->input('property_id'),
            'page'        => $request->input('page', 1),
        ]));

        $leases = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::INDEX_CACHE_TTL),
            function () use ($request) {
                $query = Lease::query()
                    ->select(['id', 'unit_id', 'tenant_id', 'start_date', 'end_date', 'rent_price', 'status', 'created_at'])
                    ->with([
                        'tenant:id,name,email,phone',
                        'unit:id,property_id,unit_number,type',
                        'unit.property:id,name',
                    ])
                    ->withSum(['payments as total_paid_sum' => fn($q) => $q->where('status', 'paid')], 'amount')
                    ->latest('start_date');

                if ($status = $request->input('status')) {
                    match ($status) {
                        'active'     => $query->activeDate(),
                        'expired'    => $query->expired(),
                        'terminated' => $query->terminated(),
                        'expiring'   => $query->expiringWithin(30),
                        default      => null,
                    };
                }

                if ($propertyId = $request->input('property_id')) {
                    $query->forProperty($propertyId);
                }

                if ($search = $request->input('search')) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('tenant', fn($t) => $t->search($search))
                            ->orWhereHas('unit', fn($u) => $u->where('unit_number', 'like', "{$search}%"));
                    });
                }

                $leases = $query->paginate(15)->withQueryString();

                $leases->getCollection()->transform(fn($l) => [
                    'id'             => $l->id,
                    'tenant'         => $l->tenant,
                    'unit'           => [
                        'id'          => $l->unit->id,
                        'unit_number' => $l->unit->unit_number,
                        'type'        => $l->unit->type,
                        'property'    => $l->unit->property,
                    ],
                    'start_date'     => $l->start_date,
                    'end_date'       => $l->end_date,
                    'rent_price'     => $l->rent_price,
                    'status'         => $l->status,
                    'is_expiring'    => $l->is_expiring,
                    'days_remaining' => $l->days_remaining,
                    'total_paid'     => $l->total_paid,
                ]);

                return $leases;
            }
        );

        return Inertia::render('Leases/Index', [
            'leases'  => $leases,
            'filters' => $request->only(['search', 'status', 'property_id']),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        return Inertia::render('Leases/Form', [
            'tenants'             => Tenant::orderBy('name')->get(['id', 'name', 'email']),
            'units'               => Unit::where('status', 'vacant')
                ->with('property:id,name')
                ->get(['id', 'property_id', 'unit_number', 'type', 'rent_price']),
            'preselected_unit_id' => $request->input('unit_id'),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_id'        => ['required', 'integer', 'exists:units,id'],
            'tenant_id'      => ['required', 'integer', 'exists:tenants,id'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after:start_date'],
            'rent_price'     => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['max:2000', ...$this->nullableString],
        ]);

        $unit = Unit::findOrFail($data['unit_id']);

        // Prevent double-booking: refuse if the unit already has an overlapping active lease.
        $hasActiveLease = Lease::forUnit($unit->id)->activeDate()->exists();

        if ($hasActiveLease) {
            return back()
                ->withErrors(['unit_id' => 'This unit already has an active lease.'])
                ->withInput();
        }

        $lease = DB::transaction(function () use ($data) {
            $lease = Lease::create([
                ...$data,
                'status'     => 'active',
                'created_by' => Auth::id(),
            ]);

            $lease->unit->update(['status' => 'occupied']);

            return $lease;
        });

        $this->invalidateCache();

        return redirect()
            ->route('leases.show', $lease)
            ->with('success', 'Lease created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Lease $lease)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "leases.show.v{$version}.{$lease->id}";

        $data = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::SHOW_CACHE_TTL),
            function () use ($lease) {
                $lease->load([
                    'tenant',
                    'unit.property:id,name,address,city',
                    'payments' => fn($q) => $q->latest('payment_date'),
                    'createdBy:id,name',
                ]);

                return [
                    'id'                  => $lease->id,
                    'tenant'              => $lease->tenant,
                    'unit'                => $lease->unit,
                    'start_date'          => $lease->start_date,
                    'end_date'            => $lease->end_date,
                    'rent_price'          => $lease->rent_price,
                    'deposit_amount'      => $lease->deposit_amount,
                    'status'              => $lease->status,
                    'notes'               => $lease->notes,
                    'terminated_at'       => $lease->terminated_at,
                    'termination_reason'  => $lease->termination_reason,
                    'created_by'          => $lease->createdBy,
                    'days_remaining'      => $lease->days_remaining,
                    'is_active'           => $lease->is_active,
                    'is_expiring'         => $lease->is_expiring,
                    'is_expired'          => $lease->is_expired,
                    'duration_months'     => $lease->duration_months,
                    'total_rent'          => $lease->total_rent,
                    'total_paid'          => $lease->total_paid,
                    'balance'             => $lease->balance,
                    'payments'            => $lease->payments,
                ];
            }
        );

        return Inertia::render('Leases/Show', ['lease' => $data]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(Lease $lease)
    {
        $lease->load(['tenant', 'unit.property']);

        return Inertia::render('Leases/Form', [
            'lease' => [
                'id'             => $lease->id,
                'unit_id'        => $lease->unit_id,
                'tenant_id'      => $lease->tenant_id,
                'start_date'     => $lease->start_date,
                'end_date'       => $lease->end_date,
                'rent_price'     => $lease->rent_price,
                'deposit_amount' => $lease->deposit_amount,
                'notes'          => $lease->notes,
                'status'         => $lease->status,
            ],
            'tenants' => Tenant::orderBy('name')->get(['id', 'name', 'email']),
            // Include the currently-assigned unit even though it's occupied —
            // it's occupied *by this lease*, so it must remain selectable when editing.
            'units'   => Unit::where('status', 'vacant')
                ->orWhere('id', $lease->unit_id)
                ->with('property:id,name')
                ->get(['id', 'property_id', 'unit_number', 'type', 'rent_price']),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, Lease $lease)
    {
        $data = $request->validate([
            'tenant_id'      => ['required', 'integer', 'exists:tenants,id'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after:start_date'],
            'rent_price'     => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['max:2000', ...$this->nullableString],
        ]);

        // unit_id is intentionally not editable here. Moving a tenant to a different
        // unit is modeled as terminate + create a new lease, so occupancy/financial
        // history per unit stays accurate instead of being silently rewritten.
        $lease->update($data);

        $this->invalidateCache();

        return redirect()
            ->route('leases.show', $lease)
            ->with('success', 'Lease updated successfully.');
    }

    // ── Terminate ─────────────────────────────────────────────────────────────
    public function terminate(Request $request, Lease $lease)
    {
        $validated = $request->validate([
            'termination_reason' => ['max:1000', ...$this->requiredString],
        ]);

        $lease->terminate($validated['termination_reason']); // also frees the unit

        $this->invalidateCache();

        return redirect()
            ->route('leases.show', $lease)
            ->with('success', 'Lease terminated.');
    }

    // ── Renew ─────────────────────────────────────────────────────────────────
    public function renew(Request $request, Lease $lease)
    {
        $validated = $request->validate([
            'months'     => ['required', 'integer', 'min:1', 'max:60'],
            'rent_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $newLease = DB::transaction(function () use ($lease, $validated) {
            $newLease = $lease->renew($validated['months'], $validated['rent_price'] ?? null);

            // Mark the old record as superseded so it stops showing as active/expiring.
            $lease->update(['status' => 'expired']);

            return $newLease;
        });

        $this->invalidateCache();

        return redirect()
            ->route('leases.show', $newLease)
            ->with('success', 'Lease renewed.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    public function destroy(Lease $lease)
    {
        // Deleting is only safe for leases with no financial history — real,
        // in-effect leases should be terminated instead so payment records survive.
        if ($lease->payments()->exists()) {
            return back()->with('error', 'Cannot delete a lease with recorded payments — terminate it instead.');
        }

        if ($lease->is_active) {
            $lease->unit->update(['status' => 'vacant']);
        }

        $lease->delete(); // Uses SoftDeletes

        $this->invalidateCache();

        return redirect()
            ->route('leases.index')
            ->with('success', 'Lease deleted.');
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────
    private function cacheVersion(): int
    {
        return Cache::get('leases.cache.version', 1);
    }

    private function invalidateCache(): void
    {
        $version = $this->cacheVersion();
        Cache::put('leases.cache.version', $version + 1, now()->addDays(30));

        // Lease changes affect unit occupancy, which PropertiesController's cached
        // index/show responses (occupied/vacant counts) depend on — bump that
        // cache too, or property pages will show stale occupancy after a lease
        // is created, terminated, or renewed.
        $propVersion = Cache::get('properties.cache.version', 1);
        Cache::put('properties.cache.version', $propVersion + 1, now()->addDays(30));
    }
}
