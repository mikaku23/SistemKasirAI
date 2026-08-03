<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visitor extends Model
{
    use SoftDeletes;

    protected $table = 'visitors';

    protected $fillable = [
        'session_token',
        'name',
        'phone',
        'email',
        'ip_address',
        'user_agent',
        'last_seen_at',
        'source',
        'metadata',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function aiConversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    public function aiHandoffs()
    {
        return $this->hasMany(AiHandoff::class);
    }

    public function aiMessages()
    {
        return $this->hasMany(AiMessages::class);
    }

    public function aiSearchLogs()
    {
        return $this->hasMany(AiSearchLog::class);
    }
}
