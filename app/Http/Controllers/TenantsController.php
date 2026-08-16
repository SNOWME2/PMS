<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Rules\ValidatorParamsRule;

class TenantsController extends Controller
{
    protected array $nullableString;
    protected array $requiredString;
    protected array $dateBeforeToday;

    private const INDEX_CACHE_TTL = 60 * 60 * 6; // 6 hours
    private const SHOW_CACHE_TTL  = 60 * 60 * 6; // 6 hours

    public function __construct()
    {
        $this->nullableString  = ValidatorParamsRule::nullableString();
        $this->requiredString  = ValidatorParamsRule::requiredString();
        $this->dateBeforeToday = ValidatorParamsRule::dateBeforeToday();
    }

    // ── Index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "tenants.index.v{$version}." . md5(json_encode([
            'search' => $request->input('search'),
            'status' => $request->input('status'), // 'active' | null
            'page'   => $request->input('page', 1),
        ]));

        $tenants = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::INDEX_CACHE_TTL),
            function () use ($request) {
                $query = Tenant::query()
                    ->select(['id', 'name', 'email', 'phone', 'id_type', 'id_number', 'city', 'created_at'])
                    ->withCount('leases')
                    ->latest();

                if ($search = $request->input('search')) {
                    $query->search($search);
                }

                if ($request->input('status') === 'active') {
                    $query->active();
                }

                $tenants = $query->paginate(15)->withQueryString();

                $tenants->getCollection()->transform(fn($t) => [
                    'id'           => $t->id,
                    'name'         => $t->name,
                    'email'        => $t->email,
                    'phone'        => $t->phone,
                    'id_type'      => $t->id_type,
                    'id_number'    => $t->id_number,
                    'city'         => $t->city,
                    'initials'     => $t->initials,
                    'leases_count' => $t->leases_count,
                ]);

                return $tenants;
            }
        );

        return Inertia::render('Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────
    public function create()
    {
        return Inertia::render('Tenants/Form');
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                     => ['max:255', ...$this->requiredString],
            'email'                    => ['max:255', 'email', Rule::unique('tenants', 'email')],
            'phone'                    => ['max:30', ...$this->nullableString],
            'id_type'                  => ['required', Rule::in(['national_id', 'passport', 'driver_license'])],
            'id_number'                => ['max:100', ...$this->requiredString],
            'date_of_birth'            => ['nullable', 'date', ...$this->dateBeforeToday],
            'address'                  => ['max:500', ...$this->nullableString],
            'city'                     => ['max:100', ...$this->nullableString],
            'province'                 => ['max:100', ...$this->nullableString],
            'postal_code'              => ['max:20', ...$this->nullableString],
            'emergency_contact_name'   => ['max:255', ...$this->nullableString],
            'emergency_contact_phone'  => ['max:30', ...$this->nullableString],
            'notes'                    => ['max:2000', ...$this->nullableString],
        ]);

        $tenant = Tenant::create($data);

        $this->invalidateCache();

        return redirect()
            ->route('tenants.show', $tenant)
            ->with('success', 'Tenant added successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Tenant $tenant)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "tenants.show.v{$version}.{$tenant->id}";

        $data = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::SHOW_CACHE_TTL),
            function () use ($tenant) {
                $tenant->load([
                    'leases' => fn($q) => $q->with([
                        'unit:id,property_id,unit_number',
                        'unit.property:id,name',
                    ]),
                ]);

                return [
                    'id'                      => $tenant->id,
                    'name'                    => $tenant->name,
                    'email'                   => $tenant->email,
                    'phone'                   => $tenant->phone,
                    'id_type'                 => $tenant->id_type,
                    'id_number'               => $tenant->id_number,
                    'date_of_birth'           => $tenant->date_of_birth,
                    'full_address'            => $tenant->full_address,
                    'emergency_contact_name'  => $tenant->emergency_contact_name,
                    'emergency_contact_phone' => $tenant->emergency_contact_phone,
                    'notes'                   => $tenant->notes,
                    'initials'                => $tenant->initials,
                    'active_lease_count'      => $tenant->active_lease_count,
                    'total_paid'              => $tenant->total_paid,
                    'leases'                  => $tenant->leases->map(fn($l) => [
                        'id'         => $l->id,
                        'unit'       => $l->unit,
                        'start_date' => $l->start_date,
                        'end_date'   => $l->end_date,
                        'rent_price' => $l->rent_price,
                        'status'     => $l->status,
                        'is_active'  => $l->is_active,
                    ]),
                ];
            }
        );

        return Inertia::render('Tenants/Show', ['tenant' => $data]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(Tenant $tenant)
    {
        return Inertia::render('Tenants/Form', [
            'tenant' => $tenant->only([
                'id',
                'name',
                'email',
                'phone',
                'id_type',
                'id_number',
                'date_of_birth',
                'address',
                'city',
                'province',
                'postal_code',
                'emergency_contact_name',
                'emergency_contact_phone',
                'notes',
            ]),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'name'                     => ['max:255', ...$this->requiredString],
            'email'                    => ['max:255', 'email', Rule::unique('tenants', 'email')->ignore($tenant->id)],
            'phone'                    => ['max:30', ...$this->nullableString],
            'id_type'                  => ['required', Rule::in(['national_id', 'passport', 'driver_license'])],
            'id_number'                => ['max:100', ...$this->requiredString],
            'date_of_birth'            => ['nullable', 'date', ...$this->dateBeforeToday],
            'address'                  => ['max:500', ...$this->nullableString],
            'city'                     => ['max:100', ...$this->nullableString],
            'province'                 => ['max:100', ...$this->nullableString],
            'postal_code'              => ['max:20', ...$this->nullableString],
            'emergency_contact_name'   => ['max:255', ...$this->nullableString],
            'emergency_contact_phone'  => ['max:30', ...$this->nullableString],
            'notes'                    => ['max:2000', ...$this->nullableString],
        ]);

        $tenant->update($data);

        $this->invalidateCache();

        return redirect()
            ->route('tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    public function destroy(Tenant $tenant)
    {
        $hasActiveLease = $tenant->leases()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->exists();

        if ($hasActiveLease) {
            return back()->with('error', 'Cannot delete a tenant with an active lease.');
        }

        $tenant->delete(); // Uses SoftDeletes

        $this->invalidateCache();

        return redirect()
            ->route('tenants.index')
            ->with('success', 'Tenant deleted.');
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────
    private function cacheVersion(): int
    {
        return Cache::get('tenants.cache.version', 1);
    }

    private function invalidateCache(): void
    {
        $version = $this->cacheVersion();

        Cache::put('tenants.cache.version', $version + 1, now()->addDays(30));
    }
}
