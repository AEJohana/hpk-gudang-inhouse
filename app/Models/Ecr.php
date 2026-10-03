<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ecr extends Model
{
    use HasFactory;

    protected $fillable = [
        'ecr_number',
        'component_id',
        'title',
        'revision_type', // spec_change, part_replacement, discontinue
        'old_specification',
        'new_specification',
        'reason',
        'document_path',
        'stock_policy', // run_out, immediate_scrap, rework
        'status', // submitted, reviewed_qc, approved, rejected
        'requested_by',
        'approved_by',
        'approval_notes',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'submitted' => ['label' => 'Diajukan (Review QC/Eng)', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'reviewed_qc' => ['label' => 'Telah Di-Review QC', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'approved' => ['label' => 'Disetujui (Approved)', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'rejected' => ['label' => 'Ditolak (Rejected)', 'class' => 'bg-rose-100 text-rose-800 border-rose-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }

    public function getRevisionTypeLabelAttribute(): string
    {
        return match($this->revision_type) {
            'spec_change' => 'Perubahan Spesifikasi Teknis',
            'part_replacement' => 'Pergantian Part Alternatif',
            'discontinue' => 'Komponen Dihentikan (Discontinue)',
            default => ucfirst($this->revision_type),
        };
    }
}
