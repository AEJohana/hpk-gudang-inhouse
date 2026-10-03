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

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function getTypeBadgeAttribute(): array
    {
        return match($this->type) {
            'inbound' => ['label' => 'Penerimaan (Inbound)', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'outbound' => ['label' => 'Pengeluaran (Outbound)', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'transfer' => ['label' => 'Transfer Lokasi', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'return' => ['label' => 'Retur Barang', 'class' => 'bg-purple-100 text-purple-800 border-purple-300'],
            default => ['label' => ucfirst($this->type), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }
}
