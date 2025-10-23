<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ViolationReport extends Model
{
    protected $fillable = [
        'reporter_id',
        'reportable_id',
        'reportable_type',
        'violation_type',
        'description',
        'status',
        'handled_by',
        'resolution_notes',
        'resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime'
    ];

    /**
     * Get the user who reported the violation
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the user who handled the violation
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Get the reportable entity (review or comment)
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for resolved reports
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for reports by violation type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('violation_type', $type);
    }
}
