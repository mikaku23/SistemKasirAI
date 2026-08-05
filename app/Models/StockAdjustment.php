<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustments';

    protected $fillable = [
        'product_id',
        'stock_batch_id',
        'location_id',
        'user_id',
        'system_qty',
        'physical_qty',
        'difference_qty',
        'adjustment_type',
        'reason',
        'adjusted_at',
        'metadata',
    ];

    protected $casts = [
        'system_qty' => 'decimal:2',
        'physical_qty' => 'decimal:2',
        'difference_qty' => 'decimal:2',
        'adjusted_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'adjustment_code',
        'review_status',
        'review_status_label',
        'review_status_class',
        'difference_label',
        'difference_direction_label',
        'review_action_label',
        'system_action_text',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatches::class)->withTrashed();
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAdjustmentCodeAttribute(): string
    {
        $date = $this->adjusted_at instanceof Carbon
            ? $this->adjusted_at->format('Ymd')
            : now()->format('Ymd');

        return sprintf('SA-%s-%05d', $date, (int) $this->id);
    }

    public function getReviewStatusAttribute(): string
    {
        return (string) data_get($this->metadata, 'review_status', 'pending_review');
    }

    public function getReviewStatusLabelAttribute(): string
    {
        return (string) data_get($this->metadata, 'review_status_label', match ($this->review_status) {
            'matched' => 'Input cocok dengan sistem',
            'system_correct' => 'Input ditolak, stok sistem dipertahankan',
            'system_updated' => 'Stok sistem diperbarui',
            default => 'Menunggu verifikasi',
        });
    }

    public function getReviewStatusClassAttribute(): string
    {
        return match ($this->review_status) {
            'matched' => 'status-pill--success',
            'system_correct' => 'status-pill--muted',
            'system_updated' => 'status-pill--success',
            default => 'status-pill--warning',
        };
    }

    public function getDifferenceLabelAttribute(): string
    {
        $difference = (int) $this->difference_qty;

        if ($difference === 0) {
            return 'Sama';
        }

        return $difference > 0
            ? '+' . number_format($difference, 0, ',', '.') . ' pcs'
            : '-' . number_format(abs($difference), 0, ',', '.') . ' pcs';
    }

    public function getDifferenceDirectionLabelAttribute(): string
    {
        $difference = (int) $this->difference_qty;

        if ($difference === 0) {
            return 'Sesuai';
        }

        return $difference > 0
            ? 'Lebih ' . number_format($difference, 0, ',', '.') . ' pcs'
            : 'Kurang ' . number_format(abs($difference), 0, ',', '.') . ' pcs';
    }

    public function getReviewActionLabelAttribute(): string
    {
        return (string) data_get($this->metadata, 'review_action_label', $this->difference_qty == 0 ? 'Tidak ada perubahan' : 'Pilih tindakan');
    }

    public function getSystemActionTextAttribute(): string
    {
        return (string) data_get($this->metadata, 'system_action_text', $this->difference_qty == 0 ? 'Stok tetap sama.' : 'Menunggu keputusan.');
    }
}
