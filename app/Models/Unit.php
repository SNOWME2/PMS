<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Property;
use App\Models\Amenity;
use App\Models\Lease;
use App\Models\MaintenanceRequest;


class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'unit_number',
        'type',
        'floor_number',
        'size_sqm',
        'rent_price',
        'deposit_amount',
        'status',
        'description',
    ];

    protected $casts = [
        'floor_number'   => 'integer',
        'size_sqm'       => 'float',
        'rent_price'     => 'float',
        'deposit_amount' => 'float',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'unit_amenities');
    }

    /**
     * The one currently active lease (end_date >= today, status = active).
     */
    // public function activeLease(): HasOne
    // {
    //     return $this->hasOne(Lease::class)
    //         ->where('status', 'active')
    //         ->where('end_date', '>=', now()->toDateString())
    //         ->latestOfMany('start_date');
    // }

    // /**
    //  * All leases, including expired and terminated ones.
    //  */
    // public function leases(): HasMany
    // {
    //     return $this->hasMany(Lease::class)->latest('start_date');
    // }

    // public function maintenanceRequests(): HasMany
    // {
    //     return $this->hasMany(MaintenanceRequest::class)->latest();
    // }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeVacant($query)
    {
        return $query->where('status', 'vacant');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }
}
