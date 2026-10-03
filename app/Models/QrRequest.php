<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'component_id',
        'label_type', // item, box, rack
        'print_qty',
        'notes',
        'status', // pending, printed
        'requested_by',
        'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'print_qty' => 'integer',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'pending' => ['label' => 'Menunggu Cetak', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'printed' => ['label' => 'Sudah Dicetak', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }
}
