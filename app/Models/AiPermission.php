<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPermission extends Model
{
    protected $table = 'ai_permissions';

    protected $fillable = [
        'intent_key',
        'controller_class',
        'controller_method',
        'module',
        'can_read',
        'can_write',
        'requires_confirmation',
        'is_active',
        'description',
        'metadata',
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_write' => 'boolean',
        'requires_confirmation' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
