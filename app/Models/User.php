<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'role_id',
        'location_id',
        'name',
        'username',
        'email',
        'no_hp',
        'password',
        'security_question',
        'security_answer',
        'avatar',
        'is_active',
        'last_login_at',
        'last_password_changed_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'last_password_changed_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    public function receivedStockBatches()
    {
        return $this->hasMany(StockBatches::class, 'received_by');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'cashier_id');
    }

    public function returns()
    {
        return $this->hasMany(Returns::class);
    }

    public function aiConversations()
    {
        return $this->hasMany(AiConversation::class);
    }

    public function aiMessages()
    {
        return $this->hasMany(AiMessages::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->relationLoaded('role')) {
            $this->load('role.permissions');
        }

        return $this->role?->permissions()->where('slug', $permission)->exists() ?? false;
    }
}
