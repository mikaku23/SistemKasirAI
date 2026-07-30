<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ai_messages extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'content',
        'target_url',
        'target_type',
        'target_id',
        'is_read',
        'read_at',
        'source_type',
        'source_id',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
