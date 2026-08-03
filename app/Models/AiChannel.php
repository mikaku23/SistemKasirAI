<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiChannel extends Model
{
    use SoftDeletes;

    protected $table = 'ai_channels';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_active',
        'description',
        'system_prompt',
        'allowed_tools',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_tools' => 'array',
        'metadata' => 'array',
    ];

    public function conversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    public function messages()
    {
        return $this->hasMany(AiMessages::class);
    }

    public function searchLogs()
    {
        return $this->hasMany(AiSearchLog::class);
    }
}
