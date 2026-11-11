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
}
