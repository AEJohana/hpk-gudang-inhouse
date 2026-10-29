<?php

namespace Tests\Unit;

use App\Models\Component;
use PHPUnit\Framework\TestCase;

class ComponentQrCodeTest extends TestCase
{
    /**
     * Test QR Code SVG generation attribute with chillerlan/php-qrcode.
     */
    public function test_qr_code_svg_attribute_generates_valid_svg(): void
    {
        $component = new Component([
            'part_number' => 'HYD-TST-001',
            'name' => 'Hydraulic Cylinder Test',
            'category' => 'hydraulic',
        ]);

        $svg = $component->qr_code_svg;

        $this->assertNotEmpty($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
        $this->assertStringContainsString('viewBox', $svg);
    }

    /**
     * Test QR composite payload contains Name ; Code ; Rack format.
     */
    public function test_qr_composite_payload_contains_name_code_and_rack(): void
    {
        $component = new Component([
            'part_number' => 'HYD-CYL-160',
            'name' => 'Hydraulic Cylinder 160',
            'default_shelf_level' => 'L2',
            'default_slot_number' => '03',
        ]);

        // When no defaultLocation model relation loaded, fallback to Gudang HPK
        $this->assertEquals('Hydraulic Cylinder 160;HYD-CYL-160;Gudang HPK', $component->qr_composite_payload);
    }

    /**
     * Test QR composite payload with assigned location relation.
     */
    public function test_qr_composite_payload_with_assigned_location(): void
    {
        $loc = new \App\Models\Location([
            'zone_code' => 'A',
            'rack_number' => 'R3',
        ]);

        $component = new Component([
            'part_number' => 'HYD-CYL-160',
            'name' => 'Hydraulic Cylinder 160',
            'default_location_id' => 1,
            'default_shelf_level' => 'L2',
            'default_slot_number' => '03',
        ]);
        $component->setRelation('defaultLocation', $loc);

        $this->assertEquals('Hydraulic Cylinder 160;HYD-CYL-160;A-R3-L2-03', $component->qr_composite_payload);
    }

    /**
     * Test QR Code Data URI generation.
     */
    public function test_qr_code_data_uri_generates_valid_base64(): void
    {
        $component = new Component([
            'part_number' => 'CSFP10300250081',
            'name' => 'Cross Member Subframe',
        ]);

        $dataUri = $component->qr_code_data_uri;

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);
    }
}
