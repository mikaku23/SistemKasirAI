<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ai_search_log extends Model
{
    protected $table = 'ai_search_logs';

    protected $fillable = [
        'ai_channel_id',
        'visitor_id',
        'user_id',
        'ai_conversation_id',
        'query_text',
        'resolved_intent',
        'result_count',
        'clicked_product_id',
        'filters',
        'confidence_score',
        'metadata',
    ];

    protected $casts = [
        'result_count' => 'integer',
        'filters' => 'array',
        'confidence_score' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function aiChannel()
    {
        return $this->belongsTo(Ai_channel::class, 'ai_channel_id');
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aiConversation()
    {
        return $this->belongsTo(Ai_conversation::class, 'ai_conversation_id');
    }

    public function clickedProduct()
    {
        return $this->belongsTo(Product::class, 'clicked_product_id');
    }
}
