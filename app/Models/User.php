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
        'work_area',
        'department',
        'department_id',
        'can_access_all_departments',
        'signature_path',
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
        'can_access_all_departments' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function departmentRel()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function canAccessAllDepartments(): bool
    {
        return (bool) ($this->can_access_all_departments || $this->hasRole('admin_gudang'));
    }

    public function getEffectiveDepartmentNameAttribute(): string
    {
        return $this->departmentRel?->name ?? $this->department ?? 'Dept Gudang';
    }

    public function canApproveWri(WorkRequest $wri): bool
    {
        // Must have permission
        if (!$this->can('work-request.approve') && !$this->hasRole('admin_gudang')) {
            return false;
        }

        // Global admin can approve anything
        if ($this->canAccessAllDepartments()) {
            return true;
        }

        $myDept = strtolower(trim($this->effective_department_name));
        $cleanMyDept = preg_replace('/[^a-z0-9]/', '', $myDept);

        // Check matching Pengaju or Penerima
        $pengaju = strtolower(trim($wri->dept_pengaju ?? ''));
        $cleanPengaju = preg_replace('/[^a-z0-9]/', '', $pengaju);

        $penerima = strtolower(trim($wri->dept_penerima ?? ''));
        $cleanPenerima = preg_replace('/[^a-z0-9]/', '', $penerima);

        return (str_contains($cleanPengaju, $cleanMyDept) || str_contains($cleanMyDept, $cleanPengaju))
            || (str_contains($cleanPenerima, $cleanMyDept) || str_contains($cleanMyDept, $cleanPenerima));
    }

    public function getWorkAreaLabelAttribute(): string
    {
        return match($this->work_area) {
            'mesin_center' => 'Mesin Center',
            'material_handling' => 'Material Handling',
            'semua', 'global' => 'Seluruh Area',
            'gudang' => 'Gudang',
            default => 'Gudang',
        };
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin_gudang', 'admin', 'supervisor']);
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['staff', 'engineering', 'qc']);
    }

    public function isOperator(): bool
    {
        return $this->hasAnyRole(['operator', 'operator_mesin', 'operator_mh']);
    }

    public function isEngineering(): bool
    {
        return $this->hasAnyRole(['engineering', 'qc', 'supervisor']);
    }

    public function getRoleBadgeAttribute(): string
    {
        $roleName = $this->roles->first()->name ?? $this->role ?? 'staff';
        
        return match($roleName) {
            'admin_gudang', 'admin' => 'Admin',
            'supervisor' => 'Supervisor',
            'staff' => 'Staff ' . ($this->work_area === 'mesin_center' ? 'Mesin' : ($this->work_area === 'material_handling' ? 'MH' : 'Gudang')),
            'operator' => 'Operator ' . ($this->work_area === 'mesin_center' ? 'Mesin' : ($this->work_area === 'material_handling' ? 'MH' : 'Gudang')),
            'operator_mesin' => 'Operator Mesin',
            'operator_mh' => 'Operator Material Handling',
            'engineering' => 'Staff Engineering',
            'qc' => 'Staff QC',
            default => ucfirst(str_replace('_', ' ', $roleName)),
        };
    }
}
