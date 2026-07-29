<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'transaction_code',
        'location_id',
        'cashier_id',
        'customer_name',
        'customer_phone',
        'shift',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'change_amount',
        'payment_method',
        'status',
        'transaction_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'transaction_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(Transaction_item::class);
    }

    public function payments()
    {
        return $this->hasMany(Transaction_payment::class);
    }

    public function returns()
    {
        return $this->morphMany(Returns::class, 'reference');
    }
}
