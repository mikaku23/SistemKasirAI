<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product_keyword extends Model
{
    protected $table = 'product_keywords';

    protected $fillable = [
        'product_id',
        'keyword',
        'weight',
        'is_auto_generated',
    ];

    protected $casts = [
        'weight' => 'integer',
        'is_auto_generated' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
