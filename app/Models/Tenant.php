<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'id_type',              // 'national_id', 'passport', 'driver_license'
        'id_number',
        'date_of_birth',
        'address',
        'city',
        'province',
        'postal_code',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * All leases this tenant has had
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class)->latest('start_date');
    }

    /**
     * Current active lease
     */
    public function activeLease()
    {
        return $this->leases()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->first();
    }

    /**
     * Payments from this tenant
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)
            ->through('leases')
            ->latest('payment_date');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Full address: street, city, province, postal
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code,
        ]);
        return implode(', ', $parts);
    }

    /**
     * Initials for avatar: "JD" from "John Doe"
     */
    public function getInitialsAttribute(): string
    {
        return implode('', array_map(fn($part) => $part[0], explode(' ', $this->name)));
    }

    /**
     * How many active leases does this tenant have?
     * (Ideally 1, but could be 0 or rarely >1 if they rent multiple units)
     */
    public function getActiveLeaseCountAttribute(): int
    {
        return $this->leases()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->count();
    }

    /**
     * Total amount this tenant has paid across all leases
     */
    public function getTotalPaidAttribute(): float
    {
        return Payment::whereHas('lease', fn($q) => $q->where('tenant_id', $this->id))
            ->where('status', 'paid')
            ->sum('amount');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Only tenants with active leases
     */
    public function scopeActive($query)
    {
        return $query->whereHas(
            'leases',
            fn($q) => $q
                ->where('status', 'active')
                ->whereDate('end_date', '>=', now())
        );
    }

    /**
     * Search by name, email, or phone
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(
            fn($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
        );
    }

    /**
     * Tenants who have not made a payment recently (potential issue)
     */
    public function scopeWithoutRecentPayment($query, int $daysAgo = 30)
    {
        return $query->whereHas(
            'leases',
            fn($q) => $q
                ->where('status', 'active')
                ->whereDoesntHave(
                    'payments',
                    fn($p) => $p
                        ->where('status', 'paid')
                        ->whereDate('payment_date', '>=', now()->subDays($daysAgo))
                )
        );
    }
}
