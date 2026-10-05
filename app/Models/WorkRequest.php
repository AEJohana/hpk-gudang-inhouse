<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'wri_number',
        'component_id',
        'requested_by_user_id',
        'target_warehouse_id',
        'target_location_id',
        'target_shelf_level',
        'target_slot_number',
        'quantity_requested',
        'quantity_produced',
        'quantity_received',
        'priority',
        'status',
        'due_date',
        'spk_reference',
        'notes',
        'received_at',
        'received_by_user_id',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_produced' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'due_date' => 'date',
        'received_at' => 'datetime',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function targetLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'target_location_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkRequestStep::class)->orderBy('step_number');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getTargetSpecificLocationCodeAttribute(): string
    {
        if (!$this->targetLocation) {
            return '-';
        }
        $area = $this->targetLocation->zone_code ?: '1';
        $rackCode = $this->targetLocation->clean_rack_code ?: 'R1';
        $level = $this->target_shelf_level ?: ($this->targetLocation->is_pallet ? 'PLT' : 'L1');
        $slot = $this->target_slot_number ? str_pad($this->target_slot_number, 2, '0', STR_PAD_LEFT) : '01';

        return "{$area}-{$rackCode}-{$level}-{$slot}";
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'submitted' => ['label' => 'Diajukan ke Mesin', 'class' => 'bg-slate-100 text-slate-800 border-slate-300'],
            'in_production' => ['label' => 'Dalam Proses Mesin', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'ready_for_warehouse' => ['label' => 'Siap Terima di Gudang', 'class' => 'bg-amber-100 text-amber-900 border-amber-300'],
            'received' => ['label' => 'Diterima di Gudang (Stok Masuk)', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-rose-100 text-rose-800 border-rose-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }

    public function getPriorityBadgeAttribute(): array
    {
        return match($this->priority) {
            'normal' => ['label' => 'Normal', 'class' => 'bg-slate-100 text-slate-700'],
            'high' => ['label' => 'Tinggi (High)', 'class' => 'bg-amber-100 text-amber-800 font-bold'],
            'urgent_line_stop' => ['label' => 'URGENT (Line Stop)', 'class' => 'bg-rose-100 text-rose-800 font-black animate-pulse'],
            default => ['label' => ucfirst($this->priority), 'class' => 'bg-slate-100 text-slate-700'],
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        $totalSteps = $this->steps->count();
        if ($totalSteps === 0) {
            return $this->status === 'received' ? 100 : 0;
        }

        $completedSteps = $this->steps->where('status', 'completed')->count();
        $stepProgress = ($completedSteps / $totalSteps) * 80; // 80% max for machine phase

        if ($this->status === 'received') {
            return 100;
        } elseif ($this->status === 'ready_for_warehouse') {
            return 90;
        }

        return (int) round($stepProgress);
    }
}
