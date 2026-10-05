<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'grid_columns',
        'grid_rows',
        'width_meters',
        'length_meters',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'grid_columns' => 'integer',
        'grid_rows' => 'integer',
        'width_meters' => 'float',
        'length_meters' => 'float',
    ];

    /**
     * Get calculated total physical area in square meters.
     */
    public function getTotalAreaSqmAttribute(): float
    {
        $w = (float) ($this->width_meters ?: 24.0);
        $l = (float) ($this->length_meters ?: 32.0);
        return round($w * $l, 2);
    }

    /**
     * Human-readable physical dimension label (e.g., "32m × 24m (768 m²)").
     */
    public function getDimensionLabelAttribute(): string
    {
        $l = (float) ($this->length_meters ?: 32.0);
        $w = (float) ($this->width_meters ?: 24.0);
        return sprintf('%s m × %s m (%s m²)', $l, $w, number_format($this->total_area_sqm, 0));
    }

    /**
     * Human-readable grid resolution label (e.g., "16 × 12 Grid").
     */
    public function getGridLabelAttribute(): string
    {
        return sprintf('%d × %d Grid', (int) ($this->grid_columns ?: 16), (int) ($this->grid_rows ?: 12));
    }

    public function zones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function locations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Ensure at least one active warehouse and default zone exist in the system.
     */
    public static function ensureDefaultWarehouseExists(): self
    {
        $warehouse = self::where('is_active', true)->first();
        if (!$warehouse) {
            $warehouse = self::first();
        }

        if (!$warehouse) {
            $warehouse = self::create([
                'code' => 'GDG-01',
                'name' => 'Gedung Utama HPK',
                'grid_columns' => 16,
                'grid_rows' => 12,
                'width_meters' => 32.0,
                'length_meters' => 24.0,
                'is_active' => true,
                'description' => 'Gedung Penyimpanan Utama Komponen Karoseri & Sasis PT Hydraxle Perkasa',
            ]);
        }

        if (Zone::where('warehouse_id', $warehouse->id)->count() === 0) {
            Zone::create([
                'warehouse_id' => $warehouse->id,
                'code' => '1',
                'name' => 'Area 1 (Penyimpanan Utama)',
                'description' => 'Area penyimpanan standar rak bertingkat dan pallet lantai',
            ]);
        }

        return $warehouse;
    }
}
