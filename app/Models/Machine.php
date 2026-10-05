<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'machine_type',
        'location_workshop',
        'status',
        'hourly_capacity',
        'operator_default',
        'description',
    ];

    public function workRequestSteps(): HasMany
    {
        return $this->hasMany(WorkRequestStep::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'ready' => ['label' => 'Siap Operasi', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'in_operation' => ['label' => 'Sedang Beroperasi', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'maintenance' => ['label' => 'Maintenance', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-slate-100 text-slate-800 border-slate-300'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->machine_type) {
            'laser_cutting' => 'Mesin Laser Cutting',
            'bending' => 'Mesin Press Brake Bending',
            'machining' => 'Mesin CNC Machining Center',
            'lathe' => 'Mesin Bubut Heavy Duty',
            'welding' => 'Stasiun Las / Sub-Assembly',
            'shearing' => 'Mesin Shearing Cutting',
            default => ucfirst(str_replace('_', ' ', $this->machine_type)),
        };
    }
}
