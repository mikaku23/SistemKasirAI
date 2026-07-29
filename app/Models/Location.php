<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
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

    public function stockOpnames()
    {
        return $this->hasMany(Stock_opname::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function returns()
    {
        return $this->hasMany(Returns::class);
    }
}
