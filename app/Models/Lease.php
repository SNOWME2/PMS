<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Lease extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'tenant_id',
        'start_date',
        'end_date',
        'rent_price',
        'deposit_amount',
        'status',
        'notes',
        'created_by',
        'terminated_at',
        'termination_reason',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'rent_price'      => 'float',
        'deposit_amount'  => 'float',
        'terminated_at'   => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Payments associated with this lease.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    /**
     * Maintenance requests during this lease period.
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class)
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Days remaining until lease end date.
     * Negative if already expired.
     */
    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Is the lease currently active?
     * (status = active AND end_date >= today)
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->end_date >= now()->toDateString();
    }

    /**
     * Is the lease expiring soon? (within 30 days)
     */
    public function getIsExpiringAttribute(): bool
    {
        return $this->is_active && $this->days_remaining >= 0 && $this->days_remaining <= 30;
    }

    /**
     * Has the lease expired?
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date < now()->toDateString() && $this->status !== 'terminated';
    }

    /**
     * Lease duration in months
     */
    public function getDurationMonthsAttribute(): int
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    /**
     * Total rent over the full lease term
     */
    public function getTotalRentAttribute(): float
    {
        $months = $this->start_date->diffInMonths($this->end_date) + 1;
        return $this->rent_price * $months;
    }

    /**
     * Total received from this tenant (sum of all payments)
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->where('status', 'paid')->sum('amount');
    }

    /**
     * Amount still owed
     */
    public function getBalanceAttribute(): float
    {
        return $this->total_rent - $this->total_paid;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Only active leases (status = 'active' AND end_date >= today)
     */
    public function scopeActiveDate($query)
    {
        return $query
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now());
    }

    /**
     * Only expired leases (end_date < today AND status != 'terminated')
     */
    public function scopeExpired($query)
    {
        return $query
            ->whereDate('end_date', '<', now())
            ->where('status', '!=', 'terminated');
    }

    /**
     * Only terminated leases
     */
    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated')->whereNotNull('terminated_at');
    }

    /**
     * Leases expiring within N days
     */
    public function scopeExpiringWithin($query, int $days = 30)
    {
        return $query
            ->where('status', 'active')
            ->whereBetween('end_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }

    /**
     * Leases for a specific unit
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * Leases for a specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Leases for a specific property (via unit→property)
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->whereHas('unit', fn($q) => $q->where('property_id', $propertyId));
    }

    /**
     * Exclude terminated leases
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'terminated');
    }

    // ── Methods ───────────────────────────────────────────────────────────────

    /**
     * Terminate the lease early with a reason.
     * Updates unit status to vacant.
     */
    public function terminate(string $reason): void
    {
        $this->update([
            'status'              => 'terminated',
            'terminated_at'       => now(),
            'termination_reason'  => $reason,
        ]);

        // Mark unit as vacant
        $this->unit->update(['status' => 'vacant']);
    }

    /**
     * Renew the lease for another period (in months).
     * Returns the new Lease instance.
     */
    public function renew(int $monthsToAdd = 12, float $newRentPrice = null): Lease
    {
        return Lease::create([
            'unit_id'          => $this->unit_id,
            'tenant_id'        => $this->tenant_id,
            'start_date'       => $this->end_date->addDay(),
            'end_date'         => $this->end_date->addMonths($monthsToAdd),
            'rent_price'       => $newRentPrice ?? $this->rent_price,
            'deposit_amount'   => $this->deposit_amount,
            'status'           => 'active',
            'created_by'       => Auth::id(),
        ]);
    }

    /**
     * Total amount billed (all months of the lease)
     */
    public function totalBilled(): float
    {
        $monthsCount = $this->start_date->diffInMonths($this->end_date) + 1;
        return $this->rent_price * $monthsCount;
    }

    /**
     * Mark lease as expired (status = 'expired') when it reaches end_date.
     * Useful for batch jobs or cron tasks.
     */
    public function markAsExpiredIfPassed(): void
    {
        if ($this->end_date < now()->toDateString() && $this->status === 'active') {
            $this->update(['status' => 'expired']);
        }
    }

    /**
     * Generate a monthly invoice for this lease.
     * Useful for billing automation.
     */
    public function createMonthlyInvoice(Carbon $forMonth): void
    {
        // Logic would integrate with a Payment or Invoice model
        // This is a placeholder for the pattern

        Payment::create([
            'lease_id'     => $this->id,
            'amount'       => $this->rent_price,
            'status'       => 'pending',
            'due_date'     => $forMonth->clone()->lastDayOfMonth(),
            'payment_date' => null,
        ]);
    }
}
