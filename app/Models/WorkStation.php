<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'area_name',
        'pic_name',
        'status',
        'description',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'active' => ['label' => 'Lini Aktif', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'inactive' => ['label' => 'Lini Istirahat/Nonaktif', 'class' => 'bg-slate-100 text-slate-800 border-slate-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }

    /**
     * Ensure the 4 standard assembly work stations exist in the system.
     */
    public static function ensureDefaultWorkStationsExist(): void
    {
        if (self::count() > 0) {
            return;
        }

        $stations = [
            [
                'code' => 'WS-DT-01',
                'name' => 'Lini Perakitan Dump Truck (Main Line)',
                'area_name' => 'Assembly Bay 1',
                'pic_name' => 'Agus Priyono (Mandor Karoseri)',
                'status' => 'active',
                'description' => 'Perakitan akhir subframe, dinding bak, dan sistem silinder hidrolik dump truck.',
            ],
            [
                'code' => 'WS-TRL-01',
                'name' => 'Lini Perakitan Trailer & Sasis Heavy Duty',
                'area_name' => 'Assembly Bay 2',
                'pic_name' => 'Slamet Riyadi (Leader Trailer)',
                'status' => 'active',
                'description' => 'Fabrikasi dan fitting suspensi, kingpin, twist lock, dan landing gear trailer kontainer.',
            ],
            [
                'code' => 'WS-SUB-01',
                'name' => 'Lini Sub-Assembly Hidrolik & Silinder Hoist',
                'area_name' => 'Precision Assembly Room',
                'pic_name' => 'Hendra Setiawan',
                'status' => 'active',
                'description' => 'Pemasangan seal kit hidrolik, valve directional kontrol, dan PTO pompa transmisi.',
            ],
            [
                'code' => 'WS-FIN-01',
                'name' => 'Lini Finishing & Pengecatan PU Karoseri',
                'area_name' => 'Spray Booth & Oven',
                'pic_name' => 'Bambang Irawan',
                'status' => 'active',
                'description' => 'Pengecatan primer epoxy, polyurethane topcoat, stiker reflektif, dan final check.',
            ],
        ];

        foreach ($stations as $s) {
            self::create($s);
        }
    }
}
