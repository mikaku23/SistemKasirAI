<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockBatches extends Model
{
    use SoftDeletes;

    protected $table = 'stock_batches';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'location_id',
        'received_by',
        'batch_code',
        'lot_number',
        'qty_received',
        'qty_remaining',
        'purchase_price',
        'production_date',
        'expired_at',
        'received_at',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'qty_received' => 'integer',
        'qty_remaining' => 'integer',
        'purchase_price' => 'integer',
        'production_date' => 'date',
        'expired_at' => 'date',
        'received_at' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = [
        'expiry_mode',
        'expiry_mode_label',
        'expiry_days_left',
        'expiry_status',
        'expiry_status_label',
        'expiry_status_class',
        'expiry_summary',
        'status_label',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'stock_batch_id');
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'stock_batch_id');
    }

    public function stockOpnameItems()
    {
        return $this->hasMany(StockOpnameItem::class, 'stock_batch_id');
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class, 'stock_batch_id');
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class, 'stock_batch_id');
    }

    public function getExpiryModeAttribute(): string
    {
        return (string) data_get($this->metadata, 'expiry_mode', $this->expired_at ? 'fixed_date' : 'none');
    }

    public function getExpiryModeLabelAttribute(): string
    {
        return match ($this->expiry_mode) {
            'fixed_date' => 'Fixed Date',
            'shelf_life' => 'Shelf Life',
            'none' => 'No Tracking',
            default => 'No Tracking',
        };
    }

    public function getExpiryDaysLeftAttribute(): ?int
    {
        $resolved = $this->resolveExpiryDate();

        if (!$resolved) {
            return null;
        }

        return now()->startOfDay()->diffInDays($resolved->copy()->startOfDay(), false);
    }

    public function getExpiryStatusAttribute(): string
    {
        if ((int) $this->qty_remaining <= 0) {
            return 'depleted';
        }

        if ($this->expiry_mode === 'none' && !$this->expired_at) {
            return 'no_tracking';
        }

        $resolved = $this->resolveExpiryDate();

        if (!$resolved) {
            return 'unknown';
        }

        $daysLeft = $this->expiry_days_left;
        $warningDays = max(0, (int) data_get($this->metadata, 'expiry_warning_days', 30));
        $graceDays = max(0, (int) data_get($this->metadata, 'expiry_grace_days', 0));

        if ($daysLeft < 0) {
            return abs($daysLeft) <= $graceDays ? 'grace_period' : 'expired';
        }

        if ($daysLeft === 0) {
            return 'expires_today';
        }

        if ($daysLeft <= $warningDays) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function getExpiryStatusLabelAttribute(): string
    {
        return match ($this->expiry_status) {
            'depleted' => 'Depleted',
            'no_tracking' => 'No Tracking',
            'unknown' => 'Unknown',
            'expired' => 'Expired',
            'grace_period' => 'Grace Period',
            'expires_today' => 'Expires Today',
            'expiring_soon' => 'Expiring Soon',
            default => 'Active',
        };
    }

    public function getExpiryStatusClassAttribute(): string
    {
        return match ($this->expiry_status) {
            'depleted' => 'status-pill--muted',
            'no_tracking' => 'status-pill--muted',
            'unknown' => 'status-pill--muted',
            'expired' => 'status-pill--danger',
            'grace_period' => 'status-pill--warning',
            'expires_today' => 'status-pill--warning',
            'expiring_soon' => 'status-pill--warning',
            default => 'status-pill--success',
        };
    }

    public function getExpirySummaryAttribute(): string
    {
        if ((int) $this->qty_remaining <= 0) {
            return 'Stok sudah habis';
        }

        if ($this->expiry_mode === 'none' && !$this->expired_at) {
            return 'Tidak dilacak';
        }

        $resolved = $this->resolveExpiryDate();

        if (!$resolved) {
            return 'Data expiry belum lengkap';
        }

        $daysLeft = $this->expiry_days_left;

        if ($daysLeft < 0) {
            return abs($daysLeft) === 1
                ? 'Lewat 1 hari'
                : 'Lewat ' . abs($daysLeft) . ' hari';
        }

        if ($daysLeft === 0) {
            return 'Expired hari ini';
        }

        if ($daysLeft === 1) {
            return 'Sisa 1 hari';
        }

        return 'Sisa ' . $daysLeft . ' hari';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'depleted' => 'Depleted',
            'no_tracking' => 'No Tracking',
            'unknown' => 'Unknown',
            'expired' => 'Expired',
            'grace_period' => 'Grace Period',
            'expires_today' => 'Expires Today',
            'expiring_soon' => 'Expiring Soon',
            'active' => 'Active',
            default => ucfirst((string) $this->status),
        };
    }

    protected function resolveExpiryDate(): ?Carbon
    {
        if ((int) $this->qty_remaining <= 0) {
            return null;
        }

        if ($this->expiry_mode === 'none' && !$this->expired_at) {
            return null;
        }

        if ($this->expiry_mode === 'fixed_date' && $this->expired_at) {
            return Carbon::parse($this->expired_at)->startOfDay();
        }

        if ($this->expiry_mode === 'shelf_life') {
            $productionDate = $this->production_date ? Carbon::parse($this->production_date)->startOfDay() : null;
            $shelfLifeDays = data_get($this->metadata, 'shelf_life_days');

            if ($productionDate && $shelfLifeDays !== null && (int) $shelfLifeDays > 0) {
                return $productionDate->copy()->addDays((int) $shelfLifeDays)->startOfDay();
            }
        }

        return $this->expired_at ? Carbon::parse($this->expired_at)->startOfDay() : null;
    }
}
