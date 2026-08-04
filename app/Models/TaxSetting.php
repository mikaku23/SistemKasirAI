<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxSetting extends Model
{
    use SoftDeletes;

    protected $table = 'tax_settings';

    protected $fillable = [
        'name',
        'code',
        'tax_type',
        'tax_value',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'tax_value' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getTaxTypeLabelAttribute(): string
    {
        return match ($this->tax_type) {
            'percent' => 'Percent',
            'fixed' => 'Fixed Amount',
            default => 'Fixed Amount',
        };
    }

    public function getDisplayValueAttribute(): string
    {
        return $this->tax_type === 'percent'
            ? $this->tax_value . '%'
            : 'Rp ' . number_format((int) $this->tax_value, 0, ',', '.');
    }
}
