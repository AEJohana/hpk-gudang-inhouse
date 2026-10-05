<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'location_id',
        'quantity',
        'shelf_level',
        'slot_number',
        'specific_location_code',
        'batch_lot_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Compute standardized location code: e.g. 1-R3-L3-05
     */
    public function getComputedLocationCodeAttribute(): string
    {
        if ($this->specific_location_code) {
            return $this->specific_location_code;
        }

        $loc = $this->location;
        if (!$loc) {
            return '1-R1-L1-01';
        }

        $area = $loc->zone_code ?: '1';
        $rackCode = $loc->rack_code ?: ('R' . (preg_replace('/[^0-9]/', '', $loc->rack_number ?: '1') ?: '1'));
        if (!str_starts_with(strtoupper($rackCode), 'R')) {
            $rackCode = 'R' . $rackCode;
        }

        $level = $this->shelf_level ?: 'L1';
        $levelNum = preg_replace('/[^0-9]/', '', $level) ?: '1';
        $levelCode = 'L' . $levelNum;

        $slot = $this->slot_number ?: '01';
        $slotNum = str_pad(preg_replace('/[^0-9]/', '', $slot) ?: '1', 2, '0', STR_PAD_LEFT);

        return "{$area}-{$rackCode}-{$levelCode}-{$slotNum}";
    }

    /**
     * Human-readable detail location text: e.g. "Area 1, Rak 3, Lantai 3 di slot No. 5"
     */
    public function getDetailLocationLabelAttribute(): string
    {
        $loc = $this->location;
        $areaName = $loc->zone_name ?? ('Area ' . ($loc->zone_code ?? '1'));
        $rackName = $loc->rack_number ?? 'Rak 1';
        $levelNum = preg_replace('/[^0-9]/', '', $this->shelf_level ?? '1') ?: '1';
        $slotNum = preg_replace('/[^0-9]/', '', $this->slot_number ?? '1') ?: '1';

        return "{$areaName}, {$rackName}, Lantai {$levelNum} di slot No. {$slotNum}";
    }
}
