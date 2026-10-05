<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkRequestStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_request_id',
        'step_number',
        'machine_id',
        'process_name',
        'status',
        'operator_name',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workRequest(): BelongsTo
    {
        return $this->belongsTo(WorkRequest::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'pending' => ['label' => 'Menunggu', 'class' => 'bg-slate-100 text-slate-700 border-slate-300'],
            'in_progress' => ['label' => 'Sedang Dikerjakan', 'class' => 'bg-blue-100 text-blue-800 border-blue-300 font-bold'],
            'completed' => ['label' => 'Selesai Mesin', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300 font-bold'],
            'skipped' => ['label' => 'Dilewati', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }
}
