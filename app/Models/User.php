<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'department',
        'phone',
        'is_active',
        'last_login_at',
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
        return $this->hasAnyRole(['admin_gudang', 'supervisor']);
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isOperator(): bool
    {
        return $this->hasRole('operator');
    }

    public function isEngineering(): bool
    {
        return $this->hasAnyRole(['engineering', 'qc', 'supervisor']);
    }

    public function getRoleBadgeAttribute(): string
    {
        // Use the first role assigned via Spatie, fallback to old logic if none
        $roleName = $this->roles->first()->name ?? $this->role ?? 'staff';
        
        return match($roleName) {
            'admin_gudang' => 'Admin Gudang',
            'supervisor' => 'Kepala Gudang / SPV',
            'operator' => 'Operator / Picker',
            'engineering' => 'Engineering Karoseri',
            'qc' => 'Quality Control',
            default => ucfirst($roleName),
        };
    }
}
