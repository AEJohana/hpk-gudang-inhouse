<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisposalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposal_id',
        'component_id',
        'from_location_id',
        'quantity',
        'condition_description',
        'proof_photo_path',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function disposal(): BelongsTo
    {
        return $this->belongsTo(Disposal::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function getProofPhotoUrlAttribute(): string
    {
        if ($this->proof_photo_path && file_exists(public_path($this->proof_photo_path))) {
            return asset($this->proof_photo_path);
        }
        return asset('images/placeholder_part.svg');
    }
}
