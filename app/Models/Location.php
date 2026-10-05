<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'storage_type', // 'rack' or 'pallet'
        'rack_code',
        'bin_level',
        'total_levels',
        'slots_per_level',
        'description',
        'max_capacity',
        'grid_x',
        'grid_y',
        'grid_w',
        'grid_h',
        'color',
        'level_slots_config',
    ];

    protected $casts = [
        'grid_x' => 'integer',
        'grid_y' => 'integer',
        'grid_w' => 'integer',
        'grid_h' => 'integer',
        'max_capacity' => 'integer',
        'total_levels' => 'integer',
        'slots_per_level' => 'integer',
        'level_slots_config' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function zone(): BelongsTo
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

    /**
     * Determine if this storage spot is a non-rack floor pallet.
     */
    public function getIsPalletAttribute(): bool
    {
        return ($this->storage_type === 'pallet' || 
                str_contains(strtolower($this->rack_number ?? ''), 'pallet') || 
                str_contains(strtolower($this->rack_code ?? ''), 'plt'));
    }

    /**
     * Storage type badge label.
     */
    public function getDisplayTypeLabelAttribute(): string
    {
        return $this->is_pallet ? 'Penyimpanan Lantai Pallet (Non-Rak)' : 'Rak Bertingkat';
    }

    public function getCleanRackCodeAttribute(): string
    {
        if ($this->rack_code) {
            return $this->rack_code;
        }

        $digits = preg_replace('/[^0-9]/', '', $this->rack_number ?? '');

        if ($this->is_pallet) {
            return $digits ? "PLT{$digits}" : 'PLT1';
        }

        return $digits ? "R{$digits}" : ($this->rack_number ?? 'R1');
    }

    public function getDisplayRackNameAttribute(): string
    {
        $num = preg_replace('/[^0-9]/', '', $this->rack_number ?? '');

        if ($this->is_pallet) {
            return $num ? "Pallet {$num}" : ($this->rack_number ?: 'Pallet 1');
        }

        if ($num) {
            return "Rak {$num}";
        }
        return $this->rack_number ?: 'Rak 1';
    }

    public function getFullRackCodeAttribute(): string
    {
        $area = $this->zone_code ?: '1';
        return "{$area}-{$this->clean_rack_code}";
    }

    public function getFullLocationCodeAttribute(): string
    {
        $parts = ["Area " . $this->zone_code];
        if ($this->display_rack_name) $parts[] = $this->display_rack_name;
        if ($this->bin_level) $parts[] = $this->bin_level;
        return implode(' - ', $parts);
    }

    public function getTotalStoredQuantityAttribute(): float
    {
        return (float) $this->stockBalances->sum('quantity');
    }

    public function getOccupancyRateAttribute(): int
    {
        if ($this->max_capacity <= 0) return 0;
        $qty = $this->total_stored_quantity;
        return (int) min(100, round(($qty / $this->max_capacity) * 100));
    }

    /**
     * Get number of slots for a specific shelf level.
     */
    public function getSlotsForLevel(int $level): int
    {
        $config = $this->level_slots_config;
        if (is_array($config)) {
            if (isset($config[$level])) {
                return max(1, (int) $config[$level]);
            }
            if (isset($config[(string) $level])) {
                return max(1, (int) $config[(string) $level]);
            }
            if (isset($config["L{$level}"])) {
                return max(1, (int) $config["L{$level}"]);
            }
        }
        return max(1, (int) ($this->slots_per_level ?: ($this->is_pallet ? 2 : 6)));
    }

    /**
     * Generate visual shelf matrix (Levels x Slots) for the interactive rack elevation drawer.
     */
    public function getSlotMatrixAttribute(): array
    {
        $totalLevels = $this->total_levels ?: ($this->is_pallet ? 1 : 4);
        $balances = $this->stockBalances->loadMissing('component');

        $matrix = [];
        // Levels top to bottom: L4, L3, L2, L1
        for ($lvl = $totalLevels; $lvl >= 1; $lvl--) {
            $levelCode = "L{$lvl}";
            $slotsPerThisLevel = $this->getSlotsForLevel($lvl);
            $slots = [];

            for ($s = 1; $s <= $slotsPerThisLevel; $s++) {
                $slotCode = str_pad($s, 2, '0', STR_PAD_LEFT);
                $fullCode = "{$this->zone_code}-{$this->clean_rack_code}-{$levelCode}-{$slotCode}";

                // Find if any stock balance occupies this level and slot
                $matchingBalance = $balances->first(function ($sb) use ($lvl, $slotCode, $fullCode) {
                    if ($sb->specific_location_code && $sb->specific_location_code === $fullCode) {
                        return true;
                    }
                    $sbLvlNum = preg_replace('/[^0-9]/', '', $sb->shelf_level ?? '');
                    $sbSlotNum = str_pad(preg_replace('/[^0-9]/', '', $sb->slot_number ?? ''), 2, '0', STR_PAD_LEFT);
                    return ((int) $sbLvlNum === $lvl && $sbSlotNum === $slotCode);
                });

                $slots[] = [
                    'slot_number' => $slotCode,
                    'full_code' => $fullCode,
                    'is_occupied' => $matchingBalance !== null,
                    'component_id' => $matchingBalance?->component_id,
                    'component_name' => $matchingBalance?->component?->name,
                    'part_number' => $matchingBalance?->component?->part_number,
                    'quantity' => (float) ($matchingBalance?->quantity ?? 0),
                    'uom' => $matchingBalance?->component?->uom ?? 'PCS',
                ];
            }

            $matrix[] = [
                'level' => $lvl,
                'level_code' => $levelCode,
                'level_label' => "Lantai {$lvl}",
                'slots_count' => $slotsPerThisLevel,
                'slots' => $slots,
            ];
        }

        return $matrix;
    }

    /**
     * Ensure at least one storage location exists in the system.
     */
    public static function ensureDefaultLocationExists(): self
    {
        $location = self::first();
        if ($location) {
            return $location;
        }

        $warehouse = Warehouse::ensureDefaultWarehouseExists();
        $zone = Zone::where('warehouse_id', $warehouse->id)->first();

        return self::create([
            'warehouse_id' => $warehouse->id,
            'zone_id' => $zone?->id,
            'zone_code' => $zone?->code ?: '1',
            'zone_name' => $zone?->name ?: 'Area 1',
            'aisle' => 'Lorong 1',
            'rack_number' => 'Rak 1',
            'storage_type' => 'rack',
            'rack_code' => 'R1',
            'bin_level' => 'Lantai 1-4',
            'total_levels' => 4,
            'slots_per_level' => 6,
            'description' => 'Rak Penyimpanan Komponen Karoseri Utama 1',
            'max_capacity' => 48,
            'grid_x' => 2,
            'grid_y' => 2,
            'grid_w' => 1,
            'grid_h' => 1,
            'color' => 'blue',
        ]);
    }
}
