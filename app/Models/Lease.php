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
        'rent_price'      => 'decimal:2',
        'deposit_amount'  => 'decimal:2',
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
     * All maintenance requests logged against this lease's unit.
     * NOTE: date-range filtering to "during this lease" moved to
     * maintenanceRequestsDuringLease() below — see comparison notes.
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'unit_id', 'unit_id');
    }

    /**
     * Maintenance requests that fall within this specific lease's date range.
     * Safe to call on a single loaded instance; do not eager-load this one.
     */
    public function maintenanceRequestsDuringLease()
    {
        return $this->maintenanceRequests()
            ->whereDate('created_at', '>=', $this->start_date)
            ->whereDate('created_at', '<=', $this->end_date);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->end_date, false);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->end_date->toDateString() >= now()->toDateString();
    }

    public function getIsExpiringAttribute(): bool
    {
        return $this->is_active && $this->days_remaining >= 0 && $this->days_remaining <= 30;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date->toDateString() < now()->toDateString() && $this->status !== 'terminated';
    }

    public function getDurationMonthsAttribute(): int
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    public function getTotalRentAttribute(): float
    {
        $months = $this->start_date->diffInMonths($this->end_date) + 1;
        return $this->rent_price * $months;
    }

    /**
     * Total received from this tenant (sum of all payments).
     * Prefer eager-loading `total_paid_sum` via withSum() when listing
     * many leases — see comparison notes for why.
     */
    public function getTotalPaidAttribute(): float
    {
        if (array_key_exists('total_paid_sum', $this->attributes)) {
            return (float) $this->attributes['total_paid_sum'];
        }

        return $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return $this->total_rent - $this->total_paid;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActiveDate($query)
    {
        return $query
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString());
    }

    public function scopeExpired($query)
    {
        return $query
            ->where('end_date', '<', now()->toDateString())
            ->where('status', '!=', 'terminated');
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated')->whereNotNull('terminated_at');
    }

    public function scopeExpiringWithin($query, int $days = 30)
    {
        return $query
            ->where('status', 'active')
            ->whereBetween('end_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }

    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->whereHas('unit', fn($q) => $q->where('property_id', $propertyId));
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'terminated');
    }

    // ── Methods ───────────────────────────────────────────────────────────────

    public function terminate(string $reason): void
    {
        $this->update([
            'status'              => 'terminated',
            'terminated_at'       => now(),
            'termination_reason'  => $reason,
        ]);

        $this->unit->update(['status' => 'vacant']);
    }

    /**
     * Renew the lease for another period (in months).
     */
    public function renew(int $monthsToAdd = 12, float $newRentPrice = null): Lease
    {
        $newStart = $this->end_date->copy()->addDay();
        $newEnd   = $newStart->copy()->addMonths($monthsToAdd);

        return Lease::create([
            'unit_id'          => $this->unit_id,
            'tenant_id'        => $this->tenant_id,
            'start_date'       => $newStart,
            'end_date'         => $newEnd,
            'rent_price'       => $newRentPrice ?? $this->rent_price,
            'deposit_amount'   => $this->deposit_amount,
            'status'           => 'active',
            'created_by'       => Auth::id(),
        ]);
    }

    public function totalBilled(): float
    {
        $monthsCount = $this->start_date->diffInMonths($this->end_date) + 1;
        return $this->rent_price * $monthsCount;
    }

    public function markAsExpiredIfPassed(): void
    {
        if ($this->end_date->toDateString() < now()->toDateString() && $this->status === 'active') {
            $this->update(['status' => 'expired']);
        }
    }

    public function createMonthlyInvoice(Carbon $forMonth): void
    {
        Payment::create([
            'lease_id'     => $this->id,
            'amount'       => $this->rent_price,
            'status'       => 'pending',
            'due_date'     => $forMonth->clone()->lastDayOfMonth(),
            'payment_date' => null,
        ]);
    }
}
