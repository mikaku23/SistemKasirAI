<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction_payment extends Model
{
    protected $table = 'transaction_payments';

    protected $fillable = [
        'transaction_id',
        'method',
        'amount',
        'reference_no',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
