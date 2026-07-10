<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Rules\ValidatorParamsRule;


class PropertiesController extends Controller
{

    protected array $nullableString;
    protected array $requiredString;
    protected array $dateBeforeToday;
    protected array $dateAfterToday;
    protected array $dateAfterOrEqualToday;
    protected array $nullableImage;
    protected array $requiredImage;
    

    public function __construct()
    {
        $this->nullableString = ValidatorParamsRule::nullableString();
        $this->requiredString = ValidatorParamsRule::requiredString();
        $this->dateBeforeToday = ValidatorParamsRule::dateBeforeToday();
        $this->dateAfterToday = ValidatorParamsRule::dateAfterToday();
        $this->dateAfterOrEqualToday = ValidatorParamsRule::dateAfterOrEqualToday();
        $this->nullableImage = ValidatorParamsRule::nullableImage();
        $this->requiredImage = ValidatorParamsRule::requiredImage();
    }
    // ── Index ─────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Property::query()
            ->withCount(['units', 'units as occupied_units_count' => fn($q) => $q->where('status', 'occupied')])
            ->with(['amenities', 'units:id,property_id,status'])
            ->latest();

        // Search
        if ($search = $request->input('search')) {
            $query->where(
                fn($q) => $q
                    ->where('name', 'like', "{$search}%")
                    ->orWhere('address', 'like', "{$search}%")
                    ->orWhere('city', 'like', "{$search}%")
            );
        }

        // Type filter
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Status filter
        if ($status = $request->input('status')) {
            match ($status) {
                'has_vacancy' => $query->whereHas('units', fn($q) => $q->where('status', 'vacant')),
                'full'        => $query->whereDoesntHave('units', fn($q) => $q->where('status', 'occupied')),
                default       => null,
            };
        }

        $properties = $query->paginate(12);

        // Map the paginated results
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

        return Inertia::render('Properties/Index', [
            'properties' => $properties,
            'filters'    => $request->only(['search', 'type', 'status']),
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────
    public function create()
    {
        return Inertia::render('Properties/Form', [
            'amenities' => Amenity::orderBy('name')->get(['id', 'name', 'icon']),
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['max:255', ...$this->requiredString],
            'address'     => ['max:500', ...$this->requiredString],
            'city'        => ['max:100', ...$this->requiredString],
            'type'        => ['required', Rule::in(['residential', 'commercial'])],
            'description' => ['max:2000', ...$this->nullableString],
            'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('properties', 'public');
        }

        $data['created_by'] = Auth::id();

        $property = Property::create($data);
        $property->amenities()->sync($request->input('amenity_ids', []));

        return redirect()->route('properties.show', $property)
            ->with('success', 'Property created successfully.');
    }

    // ── Show ──────────────────────────────────────────────────────────────────
    public function show(Property $property)
    {
        $property->load([
            'amenities',
            'units' => fn($q) => $q->orderBy('floor_number')->orderBy('unit_number'),
            'units.amenities',
            'units.activeLease.tenant',
        ]);

  

        $units = $property->units->map(fn($u) => [
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
        ]);


        return Inertia::render('Properties/Show', [
            'property' => [
                'id'             => $property->id,
                'name'           => $property->name,
                'address'        => $property->address,
                'city'           => $property->city,
                'type'           => $property->type,
                'description'    => $property->description,
                'photo'          => $property->photo,
                'total_units'    => $property->units->count(),
                'occupied_units' => $property->units->where('status', 'occupied')->count(),
                'amenities'      => $property->amenities,
                'units'          => $units,
            ],
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
            'amenities' => Amenity::orderBy('name')->get(['id', 'name', 'icon']),
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
        // Replace photo if new one uploaded
        if ($request->hasFile('photo')) {
            if ($property->photo) {
                Storage::disk('public')->delete($property->photo);
            }
            $data['photo'] = $request->file('photo')->store('properties', 'public');
        } 
        else {
            unset($data['photo']); // don't overwrite with null
            // Storage::disk('public')->delete($property->photo);
        }

        $property->update($data);
        $property->amenities()->sync($request->input('amenity_ids', []));

        return redirect()->route('properties.show', $property)
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

        return redirect()->route('properties.index')
            ->with('success', 'Property deleted.');
    }
}
