<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    protected $fillable = [
        'level',
        'channel',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
