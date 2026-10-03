<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin_gudang' || $this->role === 'supervisor';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isEngineering(): bool
    {
        return in_array($this->role, ['engineering', 'qc', 'supervisor']);
    }

    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            'admin_gudang' => 'Admin Gudang',
            'supervisor' => 'Kepala Gudang / SPV',
            'operator' => 'Operator / Picker',
            'engineering' => 'Engineering Karoseri',
            'qc' => 'Quality Control',
            default => ucfirst($this->role ?? 'Staff'),
        };
    }
}
