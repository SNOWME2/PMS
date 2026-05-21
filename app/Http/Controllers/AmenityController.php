<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AmenityController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Amenity::query();

        // Search by name
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $amenities = $query->orderBy('name')->paginate(20);

        return Inertia::render('Amenities/Index', [
            'amenities' => $amenities->through(fn($a) => [
                'id'   => $a->id,
                'name' => $a->name,
                'icon' => $a->icon,
            ]),
            'filters' => $request->only(['search']),
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('amenities'),
            ],
            'icon' => ['nullable', 'string', 'max:50'],  // lucide icon name
        ]);

        $amenity = Amenity::create($data);

        return redirect()->route('amenities.show', $amenity)
            ->with('success', 'Amenity created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Amenity $amenity)
    {
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

        return Inertia::render('Amenities/Show', [
            'amenity' => [
                'id'         => $amenity->id,
                'name'       => $amenity->name,
                'icon'       => $amenity->icon,
                'properties' => $properties,
                'units'      => $units,
            ],
        ]);
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('amenities')->ignore($amenity->id),
            ],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $amenity->update($data);

        return redirect()->route('amenities.show', $amenity)
            ->with('success', 'Amenity updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────
    public function destroy(Amenity $amenity)
    {
        // Check if amenity is in use
        $propertiesUsing = $amenity->properties()->count();
        $unitsUsing       = $amenity->units()->count();

        if ($propertiesUsing > 0 || $unitsUsing > 0) {
            return back()->with('error', 'Cannot delete an amenity that is in use. Remove it from properties and units first.');
        }

        $amenity->delete();

        return redirect()->route('amenities.index')
            ->with('success', 'Amenity deleted.');
    }
}
