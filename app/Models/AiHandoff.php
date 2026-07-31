<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ai_handoff extends Model
{
    use SoftDeletes;

    protected $table = 'ai_handoffs';

    protected $fillable = [
        'ai_conversation_id',
        'visitor_id',
        'user_id',
        'assigned_to',
        'issue_type',
        'priority',
        'status',
        'summary',
        'resolution_notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function aiConversation()
    {
        return $this->belongsTo(Ai_conversation::class, 'ai_conversation_id');
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
