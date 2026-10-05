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

    /**
     * Ensure the 5 core fabrication machines exist in the system.
     */
    public static function ensureDefaultMachinesExist(): void
    {
        if (self::count() > 0) {
            return;
        }

        $machines = [
            [
                'code' => 'MC-LC-01',
                'name' => 'Mesin Fiber Laser Cutting 6kW',
                'machine_type' => 'laser_cutting',
                'location_workshop' => 'Workshop Fabrikasi Utama (Bay 1)',
                'status' => 'ready',
                'hourly_capacity' => 12.5,
                'operator_default' => 'Joko Sutrisno (Operator Laser)',
                'description' => 'Pemotongan presisi tinggi pelat baja hitam/bordes s.d. ketebalan 25mm untuk komponen karoseri.',
            ],
            [
                'code' => 'MC-BND-01',
                'name' => 'Mesin Press Brake Bending 250T CNC',
                'machine_type' => 'bending',
                'location_workshop' => 'Workshop Fabrikasi Utama (Bay 2)',
                'status' => 'ready',
                'hourly_capacity' => 20.0,
                'operator_default' => 'Bambang Irawan (Operator Bending CNC)',
                'description' => 'Penekukan presisi sudut multi-angle untuk bracket mounting sasis dan dinding karoseri dump truck.',
            ],
            [
                'code' => 'MC-BND-02',
                'name' => 'Mesin Press Brake Bending 120T',
                'machine_type' => 'bending',
                'location_workshop' => 'Workshop Fabrikasi Presisi (Bay 3)',
                'status' => 'ready',
                'hourly_capacity' => 25.0,
                'operator_default' => 'Slamet Riyadi',
                'description' => 'Penekukan pelat tipis dan aksesoris engsel pintu karoseri serta penguat tangki.',
            ],
            [
                'code' => 'MC-SHR-01',
                'name' => 'Mesin Shearing Cutting Plate 16mm',
                'machine_type' => 'shearing',
                'location_workshop' => 'Workshop Raw Material Plate (Bay 1)',
                'status' => 'ready',
                'hourly_capacity' => 30.0,
                'operator_default' => 'Agus Priyono',
                'description' => 'Pemotongan lembaran pelat baja strip panjang sebelum proses tekuk atau press.',
            ],
            [
                'code' => 'MC-SAW-01',
                'name' => 'Mesin Bandsaw Cutting UNP & Pipa',
                'machine_type' => 'machining',
                'location_workshop' => 'Workshop Profil Baja (Bay 2)',
                'status' => 'ready',
                'hourly_capacity' => 15.0,
                'operator_default' => 'Hendra Setiawan',
                'description' => 'Pemotongan profil WF, UNP, dan hollow bar sasis trailer.',
            ],
        ];

        foreach ($machines as $m) {
            self::create($m);
        }
    }
}
