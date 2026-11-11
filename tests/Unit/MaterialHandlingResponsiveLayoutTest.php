<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MaterialHandlingResponsiveLayoutTest extends TestCase
{
    /**
     * Test Material Handling view layout has single-row tabs, no redundant counter badges, and responsive 2x2 grid filters.
     */
    public function test_material_handling_layout_is_compact_and_aligned(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/work_station_supplies/index.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Single-row tabs & refresh button
        $this->assertStringContainsString("flex items-center justify-between gap-2 w-full", $content);
        $this->assertStringContainsString("shrink-0", $content);
        $this->assertStringContainsString('title="Segarkan Data"', $content);

        // 2. Redundant header refresh button and badges removed
        $this->assertStringNotContainsString('title="Segarkan antrean tugas MH"', $content);
        $this->assertStringNotContainsString("Item Menunggu Ambil", $content);
        $this->assertStringNotContainsString("Item Siap Diambil", $content);

        // 3. Compact 2x2 grid filters on tablet/mobile and flex on desktop
        $this->assertStringContainsString("grid grid-cols-2 md:flex md:items-center gap-2 w-full", $content);
    }
}
