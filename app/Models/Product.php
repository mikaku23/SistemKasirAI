<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'unit_id',
        'supplier_id',
        'location_id',
        'name',
        'slug',
        'barcode',
        'sku',
        'description',
        'short_description',
        'image',
        'search_keywords',
        'purchase_price',
        'sale_price',
        'min_stock',
        'stock_on_hand',
        'tracks_expiry',
        'expiry_type',
        'production_date',
        'expired_at',
        'shelf_life_days',
        'expiry_warning_days',
        'expiry_grace_days',
        'is_featured',
        'is_available_online',
        'popularity_score',
        'last_sold_at',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'min_stock' => 'integer',
        'stock_on_hand' => 'integer',
        'tracks_expiry' => 'boolean',
        'expiry_type' => 'string',
        'production_date' => 'date',
        'expired_at' => 'date',
        'shelf_life_days' => 'integer',
        'expiry_warning_days' => 'integer',
        'expiry_grace_days' => 'integer',
        'is_featured' => 'boolean',
        'is_available_online' => 'boolean',
        'popularity_score' => 'decimal:2',
        'last_sold_at' => 'datetime',
        'search_keywords' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'expiry_type_label',
        'expiry_status',
        'expiry_status_label',
        'expiry_status_class',
        'expiry_days_left',
        'resolved_expiry_at',
        'expiry_summary',
    ];

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function stockBatches()
    {
        return $this->hasMany(stockBatches::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(stockMovements::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function stockOpnameItems()
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function getExpiryTypeLabelAttribute(): string
    {
        return match ($this->expiry_type) {
            'fixed_date' => 'Fixed Date',
            'shelf_life' => 'Shelf Life',
            default => 'No Tracking',
        };
    }

    public function getResolvedExpiryAtAttribute(): ?string
    {
        $resolved = $this->resolveExpiryDate();

        return $resolved?->format('Y-m-d');
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
        if (!$this->tracks_expiry || $this->expiry_type === 'none') {
            return 'no_tracking';
        }

        $resolved = $this->resolveExpiryDate();

        if (!$resolved) {
            return 'unknown';
        }

        $daysLeft = $this->expiry_days_left;
        $graceDays = max(0, (int) $this->expiry_grace_days);
        $warningDays = max(0, (int) $this->expiry_warning_days);

        if ($daysLeft < 0) {
            return abs($daysLeft) <= $graceDays ? 'grace_period' : 'expired';
        }

        if ($daysLeft === 0) {
            return 'expires_today';
        }

        if ($daysLeft <= $warningDays) {
            return 'expiring_soon';
        }

        return 'safe';
    }

    public function getExpiryStatusLabelAttribute(): string
    {
        return match ($this->expiry_status) {
            'no_tracking' => 'No Tracking',
            'unknown' => 'Unknown',
            'expired' => 'Expired',
            'grace_period' => 'Grace Period',
            'expires_today' => 'Expires Today',
            'expiring_soon' => 'Expiring Soon',
            default => 'Safe',
        };
    }

    public function getExpiryStatusClassAttribute(): string
{
    return match ($this->expiry_status) {
        'expired' => 'status-pill--danger',
        'grace_period' => 'status-pill--grace',
        'expires_today', 'expiring_soon' => 'status-pill--warning',
        'safe' => 'status-pill--success',
        default => 'status-pill--muted',
    };
}

    public function getExpirySummaryAttribute(): string
    {
        if (!$this->tracks_expiry || $this->expiry_type === 'none') {
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

    protected function resolveExpiryDate(): ?Carbon
    {
        if (!$this->tracks_expiry || $this->expiry_type === 'none') {
            return null;
        }

        if ($this->expiry_type === 'fixed_date' && $this->expired_at) {
            return Carbon::parse($this->expired_at)->startOfDay();
        }

        if (
            $this->expiry_type === 'shelf_life' &&
            $this->production_date &&
            $this->shelf_life_days !== null
        ) {
            return Carbon::parse($this->production_date)
                ->startOfDay()
                ->addDays((int) $this->shelf_life_days);
        }

        return $this->expired_at ? Carbon::parse($this->expired_at)->startOfDay() : null;
    }
}
