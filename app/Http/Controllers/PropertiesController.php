<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Rules\ValidatorParamsRule;
use Illuminate\Support\Facades\Cache;


class PropertiesController extends Controller
{
    protected array $nullableString;
    protected array $requiredString;
    protected array $dateBeforeToday;
    protected array $dateAfterToday;
    protected array $dateAfterOrEqualToday;
    protected array $nullableImage;
    protected array $requiredImage;

    /**
     * Cache TTLs. Since invalidation is now explicit (version-bumped),
     * these can be long — they're a safety net, not the primary mechanism.
     */
    private const INDEX_CACHE_TTL   = 60 * 60 * 6; // 6 hours
    private const SHOW_CACHE_TTL    = 60 * 60 * 6; // 6 hours
    private const AMENITIES_CACHE_TTL = 60 * 60;   // 1 hour

    public function __construct()
    {
        $this->nullableString        = ValidatorParamsRule::nullableString();
        $this->requiredString        = ValidatorParamsRule::requiredString();
        $this->dateBeforeToday       = ValidatorParamsRule::dateBeforeToday();
        $this->dateAfterToday        = ValidatorParamsRule::dateAfterToday();
        $this->dateAfterOrEqualToday = ValidatorParamsRule::dateAfterOrEqualToday();
        $this->nullableImage         = ValidatorParamsRule::nullableImage();
        $this->requiredImage         = ValidatorParamsRule::requiredImage();
    }

    // ── Index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $start = microtime(true);
        $version = $this->propertiesCacheVersion();

        // NOTE: the version is now part of the key itself. Bumping the version
        // (done in store/update/destroy) automatically makes every previously
        // cached page/filter combination unreachable — that IS the invalidation.
        // Without this, Cache::put('properties.cache.version', ...) does nothing.
        $cacheKey = "properties.index.v{$version}." . md5(json_encode([
            'search' => $request->input('search'),
            'type'   => $request->input('type'),
            'status' => $request->input('status'),
            'page'   => $request->input('page', 1),
        ]));

        $properties = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::INDEX_CACHE_TTL),
            function () use ($request) {
                $query = Property::query()
                    ->select(['id', 'name', 'address', 'city', 'type', 'photo', 'created_at'])
                    ->withCount([
                        'units',
                        'units as occupied_units_count' => fn($q) => $q->where('status', 'occupied'),
                        'units as vacant_units_count'   => fn($q) => $q->where('status', 'vacant'),
                    ])
                    ->with(['amenities:id,name,icon', 'units:id,property_id,status'])
                    ->latest();

                if ($search = $request->input('search')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "{$search}%")
                            ->orWhere('address', 'like', "{$search}%")
                            ->orWhere('city', 'like', "{$search}%");
                    });
                }

                if ($type = $request->input('type')) {
                    $query->where('type', $type);
                }

                if ($status = $request->input('status')) {
                    // NOTE: previously used having('vacant_units_count', ...) /
                    // havingColumn(...), referencing the withCount() subquery
                    // aliases. MySQL tolerates HAVING referencing SELECT-list
                    // aliases even without GROUP BY; PostgreSQL rejects it
                    // ("column does not exist") because HAVING is evaluated
                    // before the SELECT list is resolved. havingColumn() also
                    // isn't a real query builder method. whereHas/whereDoesntHave
                    // compile to EXISTS subqueries instead — portable across
                    // databases and don't depend on the withCount aliases at all.
                    match ($status) {
                        'has_vacancy' => $query->whereHas('units', fn($q) => $q->where('status', 'vacant')),
                        'full'        => $query->whereDoesntHave('units', fn($q) => $q->where('status', '!=', 'occupied')),
                        default       => null,
                    };
                }

                $properties = $query->paginate(12)->withQueryString();

                // Cache plain arrays, not Eloquent models — cheaper to (de)serialize
                // and avoids surprises if model casts/appends change later.
                $properties->getCollection()->transform(fn($p) => [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'address'        => $p->address,
                    'city'           => $p->city,
                    'type'           => $p->type,
                    'photo'          => $p->photo,
                    'total_units'    => $p->units_count,
                    'occupied_units' => $p->occupied_units_count,
                    'amenities'      => $p->amenities,
                    'units'          => $p->units->map(fn($u) => ['id' => $u->id, 'status' => $u->status]),
                ]);

                return $properties;
            }
        );
        logger()->info(
            'Property index total: ' .
                round((microtime(true) - $start) * 1000, 2) .
                ' ms'
        );

        return Inertia::render('Properties/Index', [
            'properties' => $properties,
            'filters'    => $request->only(['search', 'type', 'status']),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────
    public function create()
    {
        return Inertia::render('Properties/Form', [
            'amenities' => $this->cachedAmenities(),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['max:255', ...$this->requiredString],
            'address'       => ['max:500', ...$this->requiredString],
            'city'          => ['max:100', ...$this->requiredString],
            'type'          => ['required', Rule::in(['residential', 'commercial'])],
            'description'   => ['max:2000', ...$this->nullableString],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'amenity_ids'   => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('properties', 'public');
        }

        $data['created_by'] = Auth::id();

        $property = Property::create($data);
        $property->amenities()->sync($request->input('amenity_ids', []));

        $this->invalidatePropertyCache();

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'Property created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Property $property)
    {
        $version  = $this->propertiesCacheVersion();
        $cacheKey = "properties.show.v{$version}.{$property->id}";

        $data = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::SHOW_CACHE_TTL),
            function () use ($property) {
                $property->load([
                    'amenities:id,name,icon',
                    'units' => fn($q) => $q
                        ->select(['id', 'property_id', 'unit_number', 'type', 'floor_number', 'size_sqm', 'rent_price', 'deposit_amount', 'status'])
                        ->orderBy('floor_number')
                        ->orderBy('unit_number'),
                    'units.amenities:id,name,icon',
                    'units.activeLease:leases.id,leases.unit_id,leases.start_date,leases.end_date,leases.rent_price,leases.tenant_id',
                    'units.activeLease.tenant:id,name,email',
                ]);

                $occupiedUnits = 0;
                $units = $property->units->map(function ($u) use (&$occupiedUnits) {
                    if ($u->status === 'occupied') {
                        $occupiedUnits++;
                    }

                    return [
                        'id'             => $u->id,
                        'unit_number'    => $u->unit_number,
                        'type'           => $u->type,
                        'floor_number'   => $u->floor_number,
                        'size_sqm'       => $u->size_sqm,
                        'rent_price'     => $u->rent_price,
                        'deposit_amount' => $u->deposit_amount,
                        'status'         => $u->status,
                        'amenities'      => $u->amenities,
                        'active_lease'   => $u->activeLease ? [
                            'id'         => $u->activeLease->id,
                            'start_date' => $u->activeLease->start_date,
                            'end_date'   => $u->activeLease->end_date,
                            'rent_price' => $u->activeLease->rent_price,
                            'tenant'     => [
                                'id'    => $u->activeLease->tenant->id,
                                'name'  => $u->activeLease->tenant->name,
                                'email' => $u->activeLease->tenant->email,
                            ],
                        ] : null,
                    ];
                });

                return [
                    'id'             => $property->id,
                    'name'           => $property->name,
                    'address'        => $property->address,
                    'city'           => $property->city,
                    'type'           => $property->type,
                    'description'    => $property->description,
                    'photo'          => $property->photo,
                    'total_units'    => $property->units->count(),
                    'occupied_units' => $occupiedUnits,
                    'amenities'      => $property->amenities,
                    'units'          => $units,
                ];
            }
        );

        return Inertia::render('Properties/Show', [
            'property' => $data,
        ]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(Property $property)
    {
        $property->load('amenities');

        return Inertia::render('Properties/Form', [
            'property'  => [
                'id'          => $property->id,
                'name'        => $property->name,
                'address'     => $property->address,
                'city'        => $property->city,
                'type'        => $property->type,
                'description' => $property->description,
                'photo'       => $property->photo,
                'amenity_ids' => $property->amenities->pluck('id')->toArray(),
            ],
            'amenities' => $this->cachedAmenities(),
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, Property $property)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'address'       => ['required', 'string', 'max:500'],
            'city'          => ['required', 'string', 'max:100'],
            'type'          => ['required', Rule::in(['residential', 'commercial'])],
            'description'   => ['nullable', 'string', 'max:2000'],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'amenity_ids'   => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
        ]);

        Log::info('Updating property', [$data]);

        if ($request->hasFile('photo')) {
            if ($property->photo) {
                Storage::disk('public')->delete($property->photo);
            }
            $data['photo'] = $request->file('photo')->store('properties', 'public');
        } else {
            unset($data['photo']); // don't overwrite with null
        }

        $property->update($data);
        $property->amenities()->sync($request->input('amenity_ids', []));

        // Was missing before — edits never invalidated the index/show cache.
        $this->invalidatePropertyCache();

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'Property updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    public function destroy(Property $property)
    {
        // Block delete if any unit has an active lease
        $hasActiveLease = $property->units()
            ->whereHas('leases', fn($q) => $q->where('status', 'active'))
            ->exists();

        if ($hasActiveLease) {
            return back()->with('error', 'Cannot delete a property with active leases.');
        }

        if ($property->photo) {
            Storage::disk('public')->delete($property->photo);
        }

        $property->delete(); // Uses SoftDeletes

        $this->invalidatePropertyCache();

        return redirect()
            ->route('properties.index')
            ->with('success', 'Property deleted.');
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────

    /**
     * Current cache "generation" for property data. Bump it and every key
     * built from it (index listings + show pages) becomes unreachable,
     * without having to enumerate/delete individual keys — this is the
     * standard invalidation pattern for cache drivers without tag support
     * (file/database). If you're on Redis or Memcached, prefer
     * Cache::tags(['properties'])->remember(...) / ->flush() instead, which
     * is simpler and avoids the version lookup on every read.
     */
    private function propertiesCacheVersion(): int
    {
        return Cache::get('properties.cache.version', 1);
    }

    private function invalidatePropertyCache(): void
    {
        $version = $this->propertiesCacheVersion();

        Cache::put(
            'properties.cache.version',
            $version + 1,
            now()->addDays(30)
        );
    }

    /**
     * Amenities barely change and are loaded on every create/edit form —
     * cache them. If you have an AmenitiesController that creates/edits/
     * deletes amenities, add Cache::forget('amenities.list') there too;
     * otherwise this will only self-heal after the TTL expires.
     */
    private function cachedAmenities()
    {
        return Cache::remember(
            'amenities.list',
            now()->addSeconds(self::AMENITIES_CACHE_TTL),
            fn() => Amenity::orderBy('name')->get(['id', 'name', 'icon'])
        );
    }
}
