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

    /**
     * Test local photo file path resolves file from local/NAS storage or mirror.
     */
    public function test_local_photo_file_path_resolves_downloaded_file(): void
    {
        $service = new \App\Services\ComponentPhotoStorageService();
        $dummyPath = $service->resolvePublicPath('uploads/components/ASPET1061330700.jpg');
        if (!is_dir(dirname($dummyPath))) {
            @mkdir(dirname($dummyPath), 0777, true);
        }
        file_put_contents($dummyPath, str_repeat('X', 100));

        $component = new Component([
            'part_number' => 'ASPET1061330700',
            'name' => 'Test Part',
        ]);

        try {
            $localPath = $component->local_photo_file_path;

            $this->assertNotNull($localPath);
            $this->assertFileExists($localPath);
            $this->assertStringContainsString('ASPET1061330700.jpg', $localPath);
        } finally {
            @unlink($dummyPath);
        }
    }
}
