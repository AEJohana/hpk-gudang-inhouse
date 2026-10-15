<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_number',
        'name',
        'category',
        'component_category_id',
        'uom',
        'length_mm',
        'width_or_diameter_mm',
        'thickness_or_height_mm',
        'uom_id',
        'specification',
        'minimum_stock',
        'maximum_stock',
        'default_location_id',
        'default_shelf_level',
        'default_slot_number',
        'image_path',
        'qr_code_payload',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
    ];

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }

    public function componentCategory(): BelongsTo
    {
        return $this->belongsTo(ComponentCategory::class, 'component_category_id');
    }

    public function uomRelation(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function ecrs(): HasMany
    {
        return $this->hasMany(Ecr::class);
    }

    public function workRequests(): HasMany
    {
        return $this->hasMany(WorkRequest::class);
    }

    public function getTotalStockAttribute(): float
    {
        return (float) $this->stockBalances()->sum('quantity');
    }

    public function getIsLowStockAttribute(): bool
    {
        if (empty($this->minimum_stock) || $this->minimum_stock <= 0) {
            return false;
        }
        return $this->total_stock <= $this->minimum_stock;
    }

    public function getHasLocationAttribute(): bool
    {
        return !empty($this->default_location_id);
    }

    public function getDimensionFormattedAttribute(): string
    {
        $parts = [];
        if (!is_null($this->length_mm) && trim((string)$this->length_mm) !== '') {
            $p = trim((string)$this->length_mm);
            $parts[] = 'P: ' . (is_numeric(str_replace(',', '.', $p)) ? $p . 'mm' : $p);
        }
        if (!is_null($this->width_or_diameter_mm) && trim((string)$this->width_or_diameter_mm) !== '') {
            $ld = trim((string)$this->width_or_diameter_mm);
            $parts[] = 'L/D: ' . (is_numeric(str_replace(',', '.', $ld)) ? $ld . 'mm' : $ld);
        }
        if (!is_null($this->thickness_or_height_mm) && trim((string)$this->thickness_or_height_mm) !== '') {
            $t = trim((string)$this->thickness_or_height_mm);
            $parts[] = 'T: ' . (is_numeric(str_replace(',', '.', $t)) ? $t . 'mm' : $t);
        }

        return !empty($parts) ? implode(' x ', $parts) : '-';
    }

    public function getDefaultFullLocationCodeAttribute(): string
    {
        $loc = $this->defaultLocation;
        if (!$loc) return '-';
        $area = $loc->zone_code ?: '1';
        $rackCode = $loc->clean_rack_code ?: 'R1';
        $level = $this->default_shelf_level ?: 'L1';
        $levelNum = preg_replace('/[^0-9]/', '', $level) ?: '1';
        $slotNum = str_pad(preg_replace('/[^0-9]/', '', $this->default_slot_number ?: '01') ?: '01', 2, '0', STR_PAD_LEFT);
        return "{$area}-{$rackCode}-L{$levelNum}-{$slotNum}";
    }

    public function getCategoryLabelAttribute(): string
    {
        if (empty($this->category)) {
            return 'Belum Dikategorikan';
        }

        return match($this->category) {
            'subframe' => 'Subframe',
            'assy' => 'Assy',
            'accessories' => 'Aksesoris',
            'pto' => 'PTO',
            'potong_chassis' => 'Potong Chassis',
            'spo' => 'SPO',
            // Legacy fallbacks
            'hydraulic' => 'Komponen Hidrolik & Presisi',
            'raw_material' => 'Raw Material Baja',
            'fastener' => 'Hardware & Fastener',
            'electrical' => 'Electrical & Lighting',
            'chemical_paint' => 'Chemical & Cat',
            default => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }

    public function getQrCodeSvgAttribute(): string
    {
        $payload = $this->qr_code_payload ?: $this->part_number;
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'svgUseFill' => true,
            'addQuietzone' => true,
        ]);
        return (new QRCode($options))->render($payload);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) {
            if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
                // If Google Drive link, convert to direct thumbnail URL
                if (preg_match('/(?:id=|file\/d\/)([a-zA-Z0-9_-]+)/', $this->image_path, $matches)) {
                    return "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w800";
                }
                return $this->image_path;
            }

            if (file_exists(public_path($this->image_path))) {
                return asset($this->image_path);
            }
        }

        return asset('images/placeholder_part.svg');
    }
}
