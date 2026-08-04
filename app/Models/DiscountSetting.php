<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountSetting extends Model
{
    use SoftDeletes;

    protected $table = 'discount_settings';

    protected $fillable = [
        'name',
        'code',
        'discount_type',
        'discount_value',
        'minimum_total_amount',
        'starts_at',
        'ends_at',
        'priority',
        'is_default',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'minimum_total_amount' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'date',
        'priority' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getDiscountTypeLabelAttribute(): string
    {
        return match ($this->discount_type) {
            'percent' => 'Percent',
            'fixed' => 'Fixed Amount',
            default => 'Fixed Amount',
        };
    }

    public function getDisplayValueAttribute(): string
    {
        return $this->discount_type === 'percent'
            ? max(0, (int) $this->discount_value) . '%'
            : 'Rp ' . number_format(max(0, (int) $this->discount_value), 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Scheduled';
        }

        if ($this->ends_at && $this->ends_at->startOfDay()->lt(now()->startOfDay())) {
            return 'Inactive';
        }

        return 'Active';
    }

    public function getStatusClassAttribute(): string
    {
        return match ($this->status_label) {
            'Active' => 'status-pill--success',
            'Scheduled' => 'status-pill--warning',
            'Inactive' => 'status-pill--muted',
            default => 'status-pill--muted',
        };
    }

    public function isCurrentlyActive(?Carbon $at = null): bool
    {
        $at ??= now();

        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->gt($at)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->startOfDay()->lt($at->copy()->startOfDay())) {
            return false;
        }

        return true;
    }
}
