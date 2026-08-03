<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Returns extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_code',
        'return_type',
        'location_id',
        'user_id',
        'supplier_id',
        'reference_type',
        'reference_id',
        'status',
        'total_amount',
        'return_at',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'return_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class);
    }
}
