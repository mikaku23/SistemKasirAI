<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiKnowlegdeArticle extends Model
{
    use SoftDeletes;

    protected $table = 'ai_knowlegde_articles';

    protected $fillable = [
        'title',
        'question',
        'answer',
        'category',
        'tags',
        'is_active',
        'priority',
        'source_type',
        'source_id',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function source()
    {
        return $this->morphTo();
    }
}
