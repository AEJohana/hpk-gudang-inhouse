<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WorkRequestControlBarDropdownTest extends TestCase
{
    /**
     * Test Work Request control bar has been converted to a 2-column dropdown and search layout.
     */
    public function test_wri_control_bar_uses_compact_two_column_dropdown_and_search(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/work_requests/index.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert 2-column horizontal layout
        $this->assertStringContainsString('flex items-center gap-2 w-full', $content);

        // 2. Assert old horizontal scrollbar and chevrons are removed
        $this->assertStringNotContainsString('canScrollLeft', $content);
        $this->assertStringNotContainsString('canScrollRight', $content);
        $this->assertStringNotContainsString('scrollContainer', $content);

        // 3. Assert rich dropdown trigger button exists
        $this->assertStringContainsString('title="Pilih Status Work Request"', $content);
        $this->assertStringContainsString('fa-chevron-down', $content);

        // 4. Assert all 6 status options exist in dropdown with icons and badges
        $this->assertStringContainsString('switchTab(\'all\'', $content);
        $this->assertStringContainsString('switchTab(\'verifikasi\'', $content);
        $this->assertStringContainsString('switchTab(\'proses\'', $content);
        $this->assertStringContainsString('switchTab(\'siap\'', $content);
        $this->assertStringContainsString('switchTab(\'selesai\'', $content);
        $this->assertStringContainsString('switchTab(\'ditolak\'', $content);

        // 5. Assert 6 KPI summary cards include Ditolak / Batal
        $this->assertStringContainsString('\'status\' => \'ditolak\'', $content);
        $this->assertStringContainsString('Ditolak / Batal', $content);

        // 6. Assert search box exists in column 2
        $this->assertStringContainsString('placeholder="Cari No. WRI, komponen, SPK, departemen..."', $content);
    }

    /**
     * Test KPI summary cards use clean neutral borders without colored border lines.
     */
    public function test_wri_kpi_cards_have_no_colored_border_lines(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/work_requests/index.blade.php';
        $content = file_get_contents($viewPath);

        $start = strpos($content, '<!-- 1. KPI SUMMARY STATS CARDS');
        $end = strpos($content, '<!-- 2. UNIFIED WORK REQUEST CONTROL BAR');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        $kpiSection = substr($content, $start, $end - $start);

        $this->assertStringContainsString('border-slate-200/80', $kpiSection);
        $this->assertStringContainsString('grid-cols-2 sm:grid-cols-3 xl:grid-cols-6', $kpiSection);
        $this->assertStringNotContainsString('lg:grid-cols-6', $kpiSection);
        $this->assertStringContainsString('min-w-0 flex-1', $kpiSection);
        $this->assertStringContainsString('data-flip-grid', $kpiSection);
        $this->assertStringContainsString('data-flip-card', $kpiSection);
        $this->assertStringContainsString('renderTableHtml(', $content);
        foreach ([
            'border-[#0a2342]',
            'border-amber-',
            'border-blue-',
            'border-teal-',
            'border-emerald-',
            'border-rose-',
            'ring-[#0a2342]',
            'ring-amber-',
            'ring-blue-',
            'ring-teal-',
            'ring-emerald-',
            'ring-rose-',
        ] as $forbiddenClass) {
            $this->assertStringNotContainsString($forbiddenClass, $kpiSection);
        }
    }
}
