<?php

namespace App\Models;

use Database\Factories\UserFactory;
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
        'nim',
        'nip',
        'no_hp',
        'password',
        'security_question',
        'security_answer',
        'qr_code',
        'qr_url',
        'avatar',
        'email_verified_at',
        'last_login_at',
        'last_password_changed_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
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
        return $this->hasMany(Stock_batches::class, 'received_by');
    }

    public function stockMovements()
    {
        return $this->hasMany(Stock_movement::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(Stock_adjustment::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(Stock_opname::class);
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
        return $this->hasMany(Ai_conversation::class);
    }

    public function aiMessages()
    {
        return $this->hasMany(Ai_messages::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(Activity_log::class);
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
