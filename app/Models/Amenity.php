<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * Properties that have this amenity
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'property_amenities');
    }

    /**
     * Units that have this amenity
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_amenities');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }
}
