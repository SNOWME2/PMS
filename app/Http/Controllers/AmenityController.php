<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AmenityController extends Controller
{
    private const INDEX_CACHE_TTL = 60 * 60 * 6; // 6 hours
    private const SHOW_CACHE_TTL  = 60 * 60 * 6;  // 6 hours

    // ── Index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "amenities.index.v{$version}." . md5(json_encode([
            'search' => $request->input('search'),
            'page'   => $request->input('page', 1),
        ]));

        $amenities = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::INDEX_CACHE_TTL),
            function () use ($request) {
                $query = Amenity::query();

                if ($search = $request->input('search')) {
                    $query->where('name', 'like', "%{$search}%");
                }

                $amenities = $query->orderBy('name')->paginate(20)->withQueryString();

                $amenities->through(fn($a) => [
                    'id'   => $a->id,
                    'name' => $a->name,
                    'icon' => $a->icon,
                ]);

                return $amenities;
            }
        );

        return Inertia::render('Amenities/Index', [
            'amenities' => $amenities,
            'filters'   => $request->only(['search']),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────
    public function create()
    {
        return Inertia::render('Amenities/Form');
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('amenities')],
            'icon' => ['nullable', 'string', 'max:50'], // lucide icon name
        ]);

        $amenity = Amenity::create($data);

        $this->invalidateCache();

        return redirect()
            ->route('amenities.show', $amenity)
            ->with('success', 'Amenity created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Amenity $amenity)
    {
        $version  = $this->cacheVersion();
        $cacheKey = "amenities.show.v{$version}.{$amenity->id}";

        $data = Cache::remember(
            $cacheKey,
            now()->addSeconds(self::SHOW_CACHE_TTL),
            function () use ($amenity) {
                $properties = $amenity->properties()
                    ->with('units:id,property_id,status')
                    ->get()
                    ->map(fn($p) => [
                        'id'             => $p->id,
                        'name'           => $p->name,
                        'units_count'    => $p->units->count(),
                        'occupied_units' => $p->units->where('status', 'occupied')->count(),
                    ]);

                $units = $amenity->units()
                    ->with('property:id,name')
                    ->get()
                    ->map(fn($u) => [
                        'id'          => $u->id,
                        'unit_number' => $u->unit_number,
                        'property'    => [
                            'id'   => $u->property->id,
                            'name' => $u->property->name,
                        ],
                        'status'      => $u->status,
                    ]);

                return [
                    'id'         => $amenity->id,
                    'name'       => $amenity->name,
                    'icon'       => $amenity->icon,
                    'properties' => $properties,
                    'units'      => $units,
                ];
            }
        );

        return Inertia::render('Amenities/Show', ['amenity' => $data]);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(Amenity $amenity)
    {
        return Inertia::render('Amenities/Form', [
            'amenity' => [
                'id'   => $amenity->id,
                'name' => $amenity->name,
                'icon' => $amenity->icon,
            ],
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, Amenity $amenity)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('amenities')->ignore($amenity->id)],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $amenity->update($data);

        $this->invalidateCache();

        return redirect()
            ->route('amenities.show', $amenity)
            ->with('success', 'Amenity updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    public function destroy(Amenity $amenity)
    {
        // exists() short-circuits at the first match instead of counting every
        // row — count() > 0 was scanning the full join just to answer yes/no.
        $inUse = $amenity->properties()->exists() || $amenity->units()->exists();

        if ($inUse) {
            return back()->with('error', 'Cannot delete an amenity that is in use. Remove it from properties and units first.');
        }

        $amenity->delete();

        $this->invalidateCache();

        return redirect()
            ->route('amenities.index')
            ->with('success', 'Amenity deleted.');
    }

    // ── Cache helpers ─────────────────────────────────────────────────────────
    private function cacheVersion(): int
    {
        return Cache::get('amenities.cache.version', 1);
    }

    private function invalidateCache(): void
    {
        $version = $this->cacheVersion();
        Cache::put('amenities.cache.version', $version + 1, now()->addDays(30));

        // 'amenities.list' is the flat, unpaginated list PropertiesController
        // and UnitsController pull into their create/edit forms. Nothing was
        // invalidating it before, so a renamed or deleted amenity could keep
        // showing up in those dropdowns until the TTL expired.
        Cache::forget('amenities.list');
    }
}
