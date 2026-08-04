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
        'tax_setting_id',
        'discount_setting_id',
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
        'subtotal' => 'integer',
        'discount_amount' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'change_amount' => 'integer',
        'transaction_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function location(){ return $this->belongsTo(Location::class); }
    public function cashier(){ return $this->belongsTo(User::class, 'cashier_id'); }
    public function taxSetting(){ return $this->belongsTo(TaxSetting::class); }
    public function discountSetting(){ return $this->belongsTo(DiscountSetting::class); }
    public function items(){ return $this->hasMany(TransactionItem::class); }
    public function payments(){ return $this->hasMany(TransactionPayment::class); }
    public function stockMovements(){ return $this->morphMany(StockMovement::class, 'reference'); }
    public function returns(){ return $this->morphMany(Returns::class, 'reference'); }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'status-pill--success',
            'draft' => 'status-pill--muted',
            'cancelled' => 'status-pill--danger',
            'refunded' => 'status-pill--warning',
            default => 'status-pill--muted',
        };
    }

    public function getShiftLabelAttribute(): string
    {
        return match ($this->shift) {
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'night' => 'Night',
            default => ucfirst((string) $this->shift),
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash', 'qris' => 'QRIS', 'transfer' => 'Transfer', 'debit' => 'Debit', 'credit' => 'Credit', 'mixed' => 'Mixed',
            default => ucfirst((string) $this->payment_method),
        };
    }

    public function getItemDiscountTotalAttribute(): int
    {
        return (int) data_get($this->metadata, 'item_discount_total', 0);
    }
}
