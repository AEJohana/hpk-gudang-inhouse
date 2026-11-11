<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'type', // inbound, outbound, transfer, return
        'spk_number', // opsional
        'reference_document',
        'notes',
        'work_station_id',
        'work_request_id',
        'recipient_name',
        'user_id',
        'transaction_date',
        'status',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workStation(): BelongsTo
    {
        return $this->belongsTo(WorkStation::class);
    }

    public function workRequest(): BelongsTo
    {
        return $this->belongsTo(WorkRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function getIsFromWorkRequestAttribute(): bool
    {
        return !empty($this->work_request_id) 
            || str_starts_with($this->reference_document ?? '', 'WRI') 
            || str_contains($this->transaction_number ?? '', 'IN-WRI');
    }

    public function getTypeBadgeAttribute(): array
    {
        if ($this->type === 'inbound') {
            if ($this->is_from_work_request) {
                return [
                    'label' => 'Inbound (Hasil WR)',
                    'channel' => 'wr',
                    'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    'icon' => 'fa-industry',
                ];
            }
            return [
                'label' => 'Inbound (Langsung)',
                'channel' => 'manual',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-truck-ramp-box',
            ];
        }

        return match($this->type) {
            'outbound' => [
                'label' => 'Pengeluaran (Outbound)',
                'channel' => 'outbound',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-arrow-up-right-from-square',
            ],
            'transfer' => [
                'label' => 'Transfer Lokasi',
                'channel' => 'transfer',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-arrows-rotate',
            ],
            'return' => [
                'label' => 'Retur Barang',
                'channel' => 'return',
                'class' => 'bg-purple-50 text-purple-700 border-purple-200',
                'icon' => 'fa-arrow-rotate-left',
            ],
            default => [
                'label' => ucfirst($this->type),
                'channel' => 'other',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'icon' => 'fa-circle',
            ],
        };
    }
}
