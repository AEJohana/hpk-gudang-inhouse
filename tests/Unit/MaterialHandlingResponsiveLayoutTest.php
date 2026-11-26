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

        // 3. White shape frame, 3-column sub-tab row, dynamic item counter badge, and pill search + dropdown filters
        $this->assertStringContainsString("bg-white rounded-2xl sm:rounded-3xl border border-slate-200/80 shadow-sm", $content);
        $this->assertStringContainsString("grid-cols-3 gap-2 sm:gap-3", $content);
        $this->assertStringContainsString("currentFilteredCount() + ' Item'", $content);
        $this->assertStringContainsString("rounded-full bg-white border border-slate-200/80", $content);
        $this->assertStringContainsString("partials._task_list", $content);

        // 4. Unified horizontal swipeable table partial (no vertical mobile cards), synchronized labels, & contextual Ambil buttons
        $partialPath = dirname(__DIR__, 2) . '/resources/views/work_station_supplies/partials/_task_list.blade.php';
        $this->assertFileExists($partialPath);
        $partialContent = file_get_contents($partialPath);
        $this->assertStringContainsString('overflow-x-auto', $partialContent);
        $this->assertStringContainsString('min-w-[780px]', $partialContent);
        $this->assertStringNotContainsString('block md:hidden', $partialContent);
        $this->assertStringContainsString('Dalam Perjalanan', $partialContent);
        $this->assertStringContainsString('Siap Diambil', $partialContent);
        $this->assertStringContainsString('bg-rose-600', $partialContent);
        $this->assertStringContainsString('bg-emerald-600', $partialContent);
    }
}
