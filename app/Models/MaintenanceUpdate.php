<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_request_id',
        'type',                 // 'status_change', 'assigned', 'note_added', 'photo_added', 'cost_updated', 'cancelled', 'completed'
        'title',
        'description',
        'created_by',
    ];

    public $timestamps = true;
    public const UPDATED_AT = null; // immutable audit log

    // ── Relationships ─────────────────────────────────────────────────────────

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
