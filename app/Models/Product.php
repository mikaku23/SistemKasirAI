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
        'expiry_snapshots',
        'shelf_life_days',
        'expiry_warning_days',
        'expiry_grace_days',
        'promo_discount_amount',
        'promo_discount_is_active',
        'promo_starts_at',
        'promo_ends_at',
        'promo_metadata',
        'is_featured',
        'is_available_online',
        'popularity_score',
        'last_sold_at',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'integer',
        'sale_price' => 'integer',
        'min_stock' => 'integer',
        'stock_on_hand' => 'integer',
        'tracks_expiry' => 'boolean',
        'expiry_type' => 'string',
        'production_date' => 'date',
        'expired_at' => 'date',
        'expiry_snapshots' => 'array',
        'shelf_life_days' => 'integer',
        'expiry_warning_days' => 'integer',
        'expiry_grace_days' => 'integer',
        'promo_discount_amount' => 'integer',
        'promo_discount_is_active' => 'boolean',
        'promo_starts_at' => 'datetime',
        'promo_ends_at' => 'datetime',
        'promo_metadata' => 'array',
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
        'expiry_snapshot_count',
        'expiry_snapshot_items',
        'expiry_snapshot_top',
        'effective_discount_amount',
        'promo_status',
        'promo_status_label',
        'promo_status_class',
        'promo_is_running',
        'promo_remaining_days',
        'promo_period_label',
        'promo_effective_price',
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
        return $this->hasMany(StockBatches::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
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

    public function getEffectiveDiscountAmountAttribute(): int
    {
        if (! $this->isPromoRunning()) {
            return 0;
        }

        return max(0, (int) $this->promo_discount_amount);
    }

    public function getPromoIsRunningAttribute(): bool
    {
        return $this->isPromoRunning();
    }

    public function getPromoRemainingDaysAttribute(): ?int
    {
        if (! $this->promo_ends_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays(
            Carbon::parse($this->promo_ends_at)->startOfDay(),
            false
        );
    }

    public function getPromoPeriodLabelAttribute(): string
    {
        $start = $this->promo_starts_at ? Carbon::parse($this->promo_starts_at)->format('d M Y H:i') : '-';
        $end = $this->promo_ends_at ? Carbon::parse($this->promo_ends_at)->format('d M Y H:i') : '-';

        return $start . ' → ' . $end;
    }

    public function getPromoStatusAttribute(): string
    {
        if ((int) $this->promo_discount_amount <= 0) {
            return 'inactive';
        }

        if (! $this->promo_discount_is_active) {
            if ($this->promo_ends_at && Carbon::parse($this->promo_ends_at)->lt(now())) {
                return 'expired';
            }

            if ($this->promo_starts_at && Carbon::parse($this->promo_starts_at)->gt(now())) {
                return 'scheduled';
            }

            return 'inactive';
        }

        if ($this->promo_starts_at && Carbon::parse($this->promo_starts_at)->gt(now())) {
            return 'scheduled';
        }

        if ($this->promo_ends_at && Carbon::parse($this->promo_ends_at)->lt(now())) {
            return 'expired';
        }

        return 'active';
    }

    public function getPromoStatusLabelAttribute(): string
    {
        return match ($this->promo_status) {
            'active' => 'Active',
            'scheduled' => 'Scheduled',
            'expired' => 'Expired',
            default => 'Inactive',
        };
    }

    public function getPromoStatusClassAttribute(): string
    {
        return match ($this->promo_status) {
            'active' => 'status-pill--success',
            'scheduled' => 'status-pill--warning',
            'expired' => 'status-pill--danger',
            default => 'status-pill--muted',
        };
    }

    public function getPromoEffectivePriceAttribute(): int
    {
        return max(0, (int) $this->sale_price - (int) $this->effective_discount_amount);
    }

    public function getExpiryTypeLabelAttribute(): string
    {
        if ($this->tracks_expiry && $this->expiry_type === 'none') {
            return 'Auto from batches';
        }

        return match ($this->expiry_type) {
            'fixed_date' => 'Fixed Date',
            'shelf_life' => 'Shelf Life',
            default => 'No Tracking',
        };
    }

    public function getExpiryDaysLeftAttribute(): ?int
    {
        $resolved = $this->resolveExpiryDate();

        if (! $resolved) {
            return null;
        }

        return now()->startOfDay()->diffInDays($resolved->copy()->startOfDay(), false);
    }


public function getExpiryStatusAttribute(): string
{
    $snapshot = $this->expirySnapshotTop();

    if ($snapshot) {
        return (string) data_get($snapshot, 'expiry_status', 'unknown');
    }

    if (! $this->tracks_expiry) {
        return 'no_tracking';
    }

    if ($this->expiry_type === 'none') {
        return 'sync_pending';
    }

    $resolved = $this->resolveExpiryDate();

    if (! $resolved) {
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
    $snapshot = $this->expirySnapshotTop();

    if ($snapshot) {
        return (string) data_get($snapshot, 'expiry_status_label', 'Unknown');
    }

    return match ($this->expiry_status) {
        'no_tracking' => 'No Tracking',
        'sync_pending' => 'Auto from batches',
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
    $snapshot = $this->expirySnapshotTop();

    if ($snapshot) {
        return (string) data_get($snapshot, 'expiry_status_class', 'status-pill--muted');
    }

    return match ($this->expiry_status) {
        'expired' => 'status-pill--danger',
        'grace_period' => 'status-pill--warning',
        'expires_today', 'expiring_soon' => 'status-pill--warning',
        'sync_pending', 'no_tracking', 'unknown' => 'status-pill--muted',
        'safe' => 'status-pill--success',
        default => 'status-pill--muted',
    };
}

public function getExpirySummaryAttribute(): string
{
    $snapshot = $this->expirySnapshotTop();

    if ($snapshot) {
        $count = $this->expiry_snapshot_count;

        return $count > 1
            ? $count . ' batch expiry tersinkron'
            : (string) data_get($snapshot, 'expiry_summary', '1 batch expiry tersinkron');
    }

    if (! $this->tracks_expiry) {
        return 'Tidak dilacak';
    }

    if ($this->expiry_type === 'none') {
        return 'Menunggu batch untuk sinkron expiry';
    }

    $resolved = $this->resolveExpiryDate();

    if (! $resolved) {
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

public function getResolvedExpiryAtAttribute(): ?string
{
    $snapshot = $this->expirySnapshotTop();

    if ($snapshot && filled(data_get($snapshot, 'resolved_expiry_at'))) {
        return (string) data_get($snapshot, 'resolved_expiry_at');
    }

    $resolved = $this->resolveExpiryDate();

    return $resolved?->format('Y-m-d');
}

public function getExpirySnapshotItemsAttribute(): array
{
    return $this->normalizeExpirySnapshots();
}

public function getExpirySnapshotCountAttribute(): int
{
    return count($this->normalizeExpirySnapshots());
}

public function getExpirySnapshotTopAttribute(): ?array
{
    return $this->expirySnapshotTop();
}

protected function expirySnapshotTop(): ?array
{
    $items = $this->normalizeExpirySnapshots();

    return $items[0] ?? null;
}

protected function normalizeExpirySnapshots(): array
{
    $snapshots = $this->expiry_snapshots;

    if (! is_array($snapshots)) {
        return [];
    }

    $normalized = [];

    foreach ($snapshots as $snapshot) {
        if (! is_array($snapshot)) {
            continue;
        }

        $status = (string) data_get($snapshot, 'expiry_status', data_get($snapshot, 'status', 'unknown'));

        if (in_array($status, ['no_tracking', 'unknown', 'depleted'], true)) {
            continue;
        }

        $daysLeft = data_get($snapshot, 'expiry_days_left');
        $priorityRank = (int) data_get($snapshot, 'priority_rank', $this->expirySnapshotPriority($status));

        $normalized[] = array_merge($snapshot, [
            'expiry_status' => $status,
            'expiry_status_label' => (string) data_get($snapshot, 'expiry_status_label', $this->formatExpiryStatusLabel($status)),
            'expiry_status_class' => (string) data_get($snapshot, 'expiry_status_class', $this->formatExpiryStatusClass($status)),
            'expiry_summary' => (string) data_get($snapshot, 'expiry_summary', $this->formatExpirySummary($status, $daysLeft)),
            'priority_rank' => $priorityRank,
            'expiry_days_left' => $daysLeft !== null ? (int) $daysLeft : null,
        ]);
    }

    usort($normalized, function (array $left, array $right): int {
        $leftPriority = (int) ($left['priority_rank'] ?? 99);
        $rightPriority = (int) ($right['priority_rank'] ?? 99);

        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }

        $leftDays = $left['expiry_days_left'] ?? null;
        $rightDays = $right['expiry_days_left'] ?? null;
        $leftDaysRank = $leftDays === null ? PHP_INT_MAX : (int) $leftDays;
        $rightDaysRank = $rightDays === null ? PHP_INT_MAX : (int) $rightDays;

        if ($leftDaysRank !== $rightDaysRank) {
            return $leftDaysRank <=> $rightDaysRank;
        }

        return (int) data_get($left, 'batch_id', 0) <=> (int) data_get($right, 'batch_id', 0);
    });

    return $normalized;
}

protected function expirySnapshotPriority(string $status): int
{
    return match ($status) {
        'grace_period' => 0,
        'expired' => 1,
        'expires_today' => 2,
        'expiring_soon' => 3,
        'active' => 4,
        'no_tracking' => 5,
        'depleted' => 6,
        default => 7,
    };
}

protected function formatExpiryStatusLabel(string $status): string
{
    return match ($status) {
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

protected function formatExpiryStatusClass(string $status): string
{
    return match ($status) {
        'depleted', 'no_tracking', 'unknown' => 'status-pill--muted',
        'expired' => 'status-pill--danger',
        'grace_period', 'expires_today', 'expiring_soon' => 'status-pill--warning',
        default => 'status-pill--success',
    };
}

protected function formatExpirySummary(string $status, mixed $daysLeft): string
{
    $daysLeft = is_numeric($daysLeft) ? (int) $daysLeft : null;

    return match ($status) {
        'grace_period' => 'Dalam masa grace',
        'expired' => 'Sudah expired',
        'expires_today' => 'Expired hari ini',
        'expiring_soon' => 'Menjelang expired',
        'depleted' => 'Stok habis',
        'no_tracking' => 'Tidak dilacak',
        default => $daysLeft === null ? 'Tanggal expiry tersedia' : (
            $daysLeft === 1 ? 'Sisa 1 hari' : 'Sisa ' . max(0, $daysLeft) . ' hari'
        ),
    };
}

protected function isPromoRunning(): bool
    {
        if ((int) $this->promo_discount_amount <= 0 || ! $this->promo_discount_is_active) {
            return false;
        }

        $now = now();

        if ($this->promo_starts_at && Carbon::parse($this->promo_starts_at)->gt($now)) {
            return false;
        }

        if ($this->promo_ends_at && Carbon::parse($this->promo_ends_at)->lt($now)) {
            return false;
        }

        return true;
    }

    protected function resolveExpiryDate(): ?Carbon
    {
        $snapshot = $this->expirySnapshotTop();

        if ($snapshot && filled(data_get($snapshot, 'resolved_expiry_at', data_get($snapshot, 'expired_at')))) {
            return Carbon::parse(data_get($snapshot, 'resolved_expiry_at', data_get($snapshot, 'expired_at')))->startOfDay();
        }

        if (! $this->tracks_expiry || $this->expiry_type === 'none') {
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
