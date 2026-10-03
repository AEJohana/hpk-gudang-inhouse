<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposal_number',
        'disposal_type', // scrap_iron, damaged_part, expired_chemical, obsolete
        'reason',
        'estimated_weight_kg',
        'estimated_salvage_value',
        'status', // submitted, approved_qc, approved_manager, completed, rejected
        'requested_by',
        'approved_by',
        'approval_notes',
        'completed_at',
    ];

    protected $casts = [
        'estimated_weight_kg' => 'decimal:2',
        'estimated_salvage_value' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DisposalItem::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'submitted' => ['label' => 'Diajukan', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'approved_qc' => ['label' => 'Review QC Disetujui', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'approved_manager' => ['label' => 'Disetujui Kepala Gudang', 'class' => 'bg-indigo-100 text-indigo-800 border-indigo-300'],
            'completed' => ['label' => 'Selesai / Terbuang', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'rejected' => ['label' => 'Ditolak', 'class' => 'bg-rose-100 text-rose-800 border-rose-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }

    public function getDisposalTypeLabelAttribute(): string
    {
        return match($this->disposal_type) {
            'scrap_iron' => 'Scrap Besi / Potongan Baja',
            'damaged_part' => 'Komponen Cacat / Rusak Fisik',
            'expired_chemical' => 'Chemical / Cat Kadaluarsa',
            'obsolete' => 'Material Obsolete (ECR)',
            default => ucfirst(str_replace('_', ' ', $this->disposal_type)),
        };
    }
}
