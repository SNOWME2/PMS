<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Unit;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'city',
        'type',
        'description',
        'photo',
        'occupied_units',   // cached counter — updated via refreshPropertyOccupancy()
        'created_by',
    ];

    protected $casts = [
        'occupied_units' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Live occupancy percentage — use on single-property pages.
     * For index lists, use the cached `occupied_units` column instead.
     */
    public function getOccupancyPercentageAttribute(): int
    {
        $total = $this->units()->count();
        if (! $total) return 0;

        return (int) round($this->units()->where('status', 'occupied')->count() / $total * 100);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeResidential($query)
    {
        return $query->where('type', 'residential');
    }

    public function scopeCommercial($query)
    {
        return $query->where('type', 'commercial');
    }

    public function scopeHasVacancy($query)
    {
        return $query->whereHas('units', fn($q) => $q->where('status', 'vacant'));
    }
}