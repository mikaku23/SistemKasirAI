<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ai_conversation_message extends Model
{
    protected $table = 'ai_conversation_messages';

    protected $fillable = [
        'ai_conversation_id',
        'sender_type',
        'message',
        'intent_key',
        'payload',
        'status',
        'confirmed_at',
        'executed_at',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'confirmed_at' => 'datetime',
        'executed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(Ai_conversation::class, 'ai_conversation_id');
    }
}
