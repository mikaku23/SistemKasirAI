<?php

namespace App\Models;

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
        'qty_received' => 'decimal:2',
        'qty_remaining' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'production_date' => 'date',
        'expired_at' => 'date',
        'received_at' => 'date',
        'metadata' => 'array',
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
        return $this->hasMany(Stock_movement::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(Stock_adjustment::class);
    }

    public function stockOpnameItems()
    {
        return $this->hasMany(Stock_opname_item::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(Transaction_item::class);
    }

    public function returnItems()
    {
        return $this->hasMany(Return_item::class);
    }
}

