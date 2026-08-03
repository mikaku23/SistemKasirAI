<?php

namespace App\Models;

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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(Stock_batches::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
