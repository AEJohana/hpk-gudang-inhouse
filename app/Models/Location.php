<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'zone_id',
        'zone_code',
        'zone_name',
        'aisle',
        'rack_number',
        'bin_level',
        'description',
        'max_capacity',
    ];

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class, 'default_location_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function getFullLocationCodeAttribute(): string
    {
        $parts = ["Zona " . $this->zone_code];
        if ($this->aisle) $parts[] = $this->aisle;
        if ($this->rack_number) $parts[] = $this->rack_number;
        if ($this->bin_level) $parts[] = $this->bin_level;
        return implode(' - ', $parts);
    }
}
