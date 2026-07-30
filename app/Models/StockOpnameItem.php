<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock_opname_item extends Model
{
    protected $table = 'stock_opname_items';

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'stock_batch_id',
        'system_qty',
        'physical_qty',
        'difference_qty',
        'result_type',
        'notes',
    ];

    protected $casts = [
        'system_qty' => 'decimal:2',
        'physical_qty' => 'decimal:2',
        'difference_qty' => 'decimal:2',
    ];

    public function stockOpname()
    {
        return $this->belongsTo(Stock_opname::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(Stock_batches::class);
    }
}
