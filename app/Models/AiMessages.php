<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessages extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'ai_channel_id',
        'user_id',
        'visitor_id',
        'ai_conversation_id',
        'recipient_type',
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

    public function aiConversation()
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function target()
    {
        return $this->morphTo('target');
    }

    public function source()
    {
        return $this->morphTo('source');
    }
}
