<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CycleCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'count_number',
        'zone_target',
        'count_date',
        'notes',
        'status', // in_progress, pending_review, reconciled
        'conducted_by',
        'approved_by',
        'reconciled_at',
    ];

    protected $casts = [
        'count_date' => 'date',
        'reconciled_at' => 'datetime',
    ];

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CycleCountItem::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'in_progress' => ['label' => 'Sedang Dihitung Fisik', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'pending_review' => ['label' => 'Menunggu Review SPV', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'reconciled' => ['label' => 'Selesai Rekonsiliasi', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }
}
