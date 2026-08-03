<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $fillable = [
        'ai_channel_id',
        'user_id',
        'visitor_id',
        'conversation_type',
        'title',
        'status',
        'last_intent_key',
        'last_message_at',
        'last_activity_at',
        'is_handoff',
        'metadata',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_handoff' => 'boolean',
        'metadata' => 'array',
    ];

    public function aiChannel()
    {
        return $this->belongsTo(AiChannel::class, 'ai_channel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function messages()
    {
        return $this->hasMany(AiConversationMessage::class);
    }
}
