<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversationMessage extends Model
{
    protected $table = 'ai_conversation_messages';

    protected $fillable = [
        'ai_conversation_id',
        'ai_channel_id',
        'sender_type',
        'sender_role',
        'message_type',
        'message',
        'intent_key',
        'confidence_score',
        'tool_name',
        'tool_payload',
        'tool_result',
        'status',
        'requires_confirmation',
        'requires_handoff',
        'confirmed_at',
        'executed_at',
        'payload',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'tool_payload' => 'array',
        'tool_result' => 'array',
        'confirmed_at' => 'datetime',
        'executed_at' => 'datetime',
        'metadata' => 'array',
        'requires_confirmation' => 'boolean',
        'requires_handoff' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function aiChannel()
    {
        return $this->belongsTo(AiChannel::class, 'ai_channel_id');
    }
}
