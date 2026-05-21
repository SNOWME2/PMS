<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lease_id',
        'amount',
        'status',
        'due_date',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'payment_date' => 'date',
        'amount'       => 'float',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Is this payment overdue?
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date < now()->toDateString();
    }

    /**
     * Days overdue (negative if not overdue yet)
     */
    public function getDaysOverdueAttribute(): int
    {
        if ($this->status !== 'pending') return 0;
        return now()->diffInDays($this->due_date, false);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query
            ->where('status', 'pending')
            ->whereDate('due_date', '<', now());
    }

    /**
     * Payments due for a specific month
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereMonth('due_date', $month)
            ->whereYear('due_date', $year);
    }

    /**
     * Received payments (with payment_date set)
     */
    public function scopeReceived($query)
    {
        return $query->whereNotNull('payment_date');
    }
}
