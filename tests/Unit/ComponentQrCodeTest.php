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
}
