<?php

namespace App\Models;

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
        'barcode',
        'sku',
        'description',
        'image',
        'purchase_price',
        'sale_price',
        'min_stock',
        'stock_on_hand',
        'tracks_expiry',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'stock_on_hand' => 'decimal:2',
        'tracks_expiry' => 'boolean',
        'is_active' => 'boolean',
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
        return $this->hasMany(Stock_batches::class);
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
