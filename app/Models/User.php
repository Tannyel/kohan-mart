<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'birthday',
        'password',
        'role',
        'points_balance',
        'otp_code',
        'otp_expires_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'birthday' => 'date',
            'points_balance' => 'decimal:2',
            'password' => 'hashed',
        ];
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isBirthdayMonth(): bool
    {
        return $this->birthday !== null && $this->birthday->month === now()->month;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class)->latest();
    }
}