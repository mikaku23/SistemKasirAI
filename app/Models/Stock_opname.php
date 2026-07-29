<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock_opname extends Model
{
    protected $table = 'stock_opnames';

    protected $fillable = [
        'opname_code',
        'location_id',
        'user_id',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(Stock_opname_item::class);
    }
}
