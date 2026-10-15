<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_request_id',
        'action_type',
        'status_label',
        'quantity',
        'quantity_accumulated',
        'operator_name',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_accumulated' => 'decimal:2',
    ];

    public function workRequest(): BelongsTo
    {
        return $this->belongsTo(WorkRequest::class);
    }
}
