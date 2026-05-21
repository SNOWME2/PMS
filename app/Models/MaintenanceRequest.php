<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MaintenanceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'tenant_id',
        'title',
        'description',
        'category',                     // 'plumbing', 'electrical', 'hvac', 'appliance', 'paint', 'cleaning', 'structural', 'other'
        'priority',                     // 'low', 'normal', 'high', 'urgent'
        'status',                       // 'open', 'assigned', 'in-progress', 'completed', 'cancelled'
        'assigned_to',                  // user_id of maintenance staff
        'assigned_at',
        'started_at',
        'completed_at',
        'estimated_cost',
        'actual_cost',
        'notes',
        'internal_notes',               // admin-only notes
        'photos_before',                // JSON array of photo paths
        'photos_after',
        'created_by',
    ];

    protected $casts = [
        'estimated_cost' => 'float',
        'actual_cost'    => 'float',
        'assigned_at'    => 'datetime',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'photos_before'  => 'array',
        'photos_after'   => 'array',
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

    /**
     * Staff member assigned to this request
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * User who created the request
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Status updates/history (timeline of changes)
     */
    public function updates(): HasMany
    {
        return $this->hasMany(MaintenanceUpdate::class)->latest();
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Days since request was created
     */
    public function getDaysAgoAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Is this request overdue? (urgent/high priority older than 7 days, normal older than 14 days)
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }

        $maxDays = match ($this->priority) {
            'urgent' => 1,
            'high'   => 3,
            'normal' => 7,
            'low'    => 14,
            default  => 14,
        };

        return $this->created_at->diffInDays(now()) > $maxDays;
    }

    /**
     * Time to resolution (from created_at to completed_at)
     */
    public function getResolutionTimeAttribute(): ?int
    {
        if (! $this->completed_at) return null;
        return $this->created_at->diffInHours($this->completed_at);
    }

    /**
     * How long has it been in progress?
     */
    public function getTimeInProgressAttribute(): ?int
    {
        if (! $this->started_at) return null;
        $end = $this->completed_at ?? now();
        return $this->started_at->diffInMinutes($end);
    }

    /**
     * Cost overrun: actual vs estimated
     */
    public function getCostOverrunAttribute(): float
    {
        if (! $this->estimated_cost || ! $this->actual_cost) return 0;
        return $this->actual_cost - $this->estimated_cost;
    }

    /**
     * Has photos been uploaded?
     */
    public function getHasPhotosAttribute(): bool
    {
        return ! empty($this->photos_before) || ! empty($this->photos_after);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Only open requests (status = 'open')
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Only assigned requests
     */
    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned')->whereNotNull('assigned_to');
    }

    /**
     * Only in-progress requests
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in-progress');
    }

    /**
     * Only completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Only cancelled requests
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Only active (not completed or cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Filter by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Filter by assigned staff member
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Filter by unit
     */
    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * Filter by property
     */
    public function scopeForProperty($query, int $propertyId)
    {
        return $query->whereHas('unit', fn($q) => $q->where('property_id', $propertyId));
    }

    /**
     * Created within last N days
     */
    public function scopeRecentDays($query, int $days)
    {
        return $query->whereDate('created_at', '>=', now()->subDays($days));
    }

    /**
     * Overdue requests (based on priority)
     */
    public function scopeOverdue($query)
    {
        return $query
            ->active()
            ->where(function ($q) {
                $q->where(function ($q2) {
                    // Urgent > 1 day
                    $q2->where('priority', 'urgent')
                        ->whereDate('created_at', '<', now()->subDay());
                })
                    ->orWhere(function ($q2) {
                        // High > 3 days
                        $q2->where('priority', 'high')
                            ->whereDate('created_at', '<', now()->subDays(3));
                    })
                    ->orWhere(function ($q2) {
                        // Normal > 7 days
                        $q2->where('priority', 'normal')
                            ->whereDate('created_at', '<', now()->subDays(7));
                    })
                    ->orWhere(function ($q2) {
                        // Low > 14 days
                        $q2->where('priority', 'low')
                            ->whereDate('created_at', '<', now()->subDays(14));
                    });
            });
    }

    /**
     * Unassigned requests
     */
    public function scopeUnassigned($query)
    {
        return $query->active()->whereNull('assigned_to');
    }

    // ── Methods ───────────────────────────────────────────────────────────────

    /**
     * Assign this request to a staff member and record the assignment
     */
    public function assignTo(User $user, string $notes = null): void
    {
        $this->update([
            'assigned_to' => $user->id,
            'assigned_at' => now(),
            'status'      => 'assigned',
        ]);

        $this->logUpdate('assigned', "Assigned to {$user->name}", $notes);
    }

    /**
     * Mark as in-progress
     */
    public function markInProgress(string $notes = null): void
    {
        $this->update([
            'status'     => 'in-progress',
            'started_at' => $this->started_at ?? now(),
        ]);

        $this->logUpdate('in-progress', 'Work started', $notes);
    }

    /**
     * Mark as completed
     */
    public function markCompleted(float $actualCost = null, string $notes = null): void
    {
        $this->update([
            'status'        => 'completed',
            'completed_at'  => now(),
            'actual_cost'   => $actualCost ?? $this->actual_cost,
        ]);

        // Mark unit as maintained (keep as occupied or whatever status it was)
        if ($this->unit->status === 'maintenance') {
            $this->unit->update(['status' => 'occupied']);
        }

        $this->logUpdate('completed', 'Work completed', $notes);
    }

    /**
     * Cancel the request
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status'       => 'cancelled',
            'completed_at' => now(),
        ]);

        $this->logUpdate('cancelled', 'Request cancelled', $reason);
    }

    /**
     * Log a status update/activity
     */
    public function logUpdate(string $type, string $title, string $description = null): void
    {
        MaintenanceUpdate::create([
            'maintenance_request_id' => $this->id,
            'type'                   => $type,
            'title'                  => $title,
            'description'            => $description,
            'created_by'             => Auth::id() ,
        ]);
    }

    /**
     * Add photos (before and after)
     */
    public function addPhotoBefore(string $photoPath): void
    {
        $photos = $this->photos_before ?? [];
        $photos[] = $photoPath;
        $this->update(['photos_before' => $photos]);
    }

    public function addPhotoAfter(string $photoPath): void
    {
        $photos = $this->photos_after ?? [];
        $photos[] = $photoPath;
        $this->update(['photos_after' => $photos]);
    }

    /**
     * Category and priority labels for display
     */
    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'plumbing'   => 'Plumbing',
            'electrical' => 'Electrical',
            'hvac'       => 'HVAC',
            'appliance'  => 'Appliance',
            'paint'      => 'Paint & Walls',
            'cleaning'   => 'Cleaning',
            'structural' => 'Structural',
            'other'      => 'Other',
            default      => ucfirst($category),
        };
    }

    public static function priorityLabel(string $priority): string
    {
        return match ($priority) {
            'urgent' => '🔴 Urgent',
            'high'   => '🟠 High',
            'normal' => '🟡 Normal',
            'low'    => '🟢 Low',
            default  => ucfirst($priority),
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'open'        => 'Open',
            'assigned'    => 'Assigned',
            'in-progress' => 'In Progress',
            'completed'   => 'Completed',
            'cancelled'   => 'Cancelled',
            default       => ucfirst($status),
        };
    }
}
