<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WorkRequestTablePartialStatusTest extends TestCase
{
    /**
     * Test Work Request table view implements reactive balance check for partial transfers.
     */
    public function test_table_content_has_reactive_mh_pickup_balance_check(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/work_requests/partials/_table_content.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert reactive calculation variables exist
        $this->assertStringContainsString('$cleanNext = strtolower(trim(preg_replace(\'/^Parsial\s*>\s*/i\', \'\', $nextStatus ?: \'\')));', $content);
        $this->assertStringContainsString('$hasRemainingMHPickup = !$wri->is_final', $content);
        $this->assertStringContainsString('in_array($cleanNext, [\'siap diambil\', \'menuju pengaju\'])', $content);
        $this->assertStringContainsString('str_starts_with($cleanNext, \'mh pickup\')', $content);

        // 2. Assert isWaitingMH incorporates $hasRemainingMHPickup
        $this->assertStringContainsString('$hasRemainingMHPickup', $content);

        // 3. Assert quick shortcut Buka Menu MH exists with proper title and route
        $this->assertStringContainsString('route(\'work-station-supplies.index\')', $content);
        $this->assertStringContainsString('Buka Menu MH', $content);
        $this->assertStringContainsString('Menunggu Diambil MH', $content);
    }

    /**
     * Test STS PENERIMA badge design is standardized across approval states.
     */
    public function test_sts_penerima_badge_design_is_standardized(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/work_requests/partials/_table_content.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // Assert Menunggu Pengaju badge uses identical pill geometry and single-line format
        $this->assertStringContainsString(
            '<span class="inline-flex items-center px-2 py-1 text-slate-700 bg-slate-100 border border-slate-300 rounded-lg text-[11px] font-bold whitespace-nowrap" title="Menunggu persetujuan Ka. Bagian Pengaju terlebih dahulu">',
            $content
        );
        $this->assertStringContainsString(
            '<i class="fa-solid fa-clock text-slate-400 me-1 text-[9px]"></i> Menunggu Pengaju',
            $content
        );

        // Assert Belum Verifikasi badge uses the same pill geometry
        $this->assertStringContainsString(
            '<span class="inline-flex items-center px-2 py-1 text-slate-700 bg-slate-100 border border-slate-300 rounded-lg text-[11px] font-bold whitespace-nowrap" title="Menunggu verifikasi oleh Ka. {{ $wri->dept_penerima ?: \'Penerima\' }}">',
            $content
        );
        $this->assertStringContainsString(
            '<i class="fa-solid fa-clock text-slate-400 me-1 text-[9px]"></i> Belum Verifikasi',
            $content
        );
    }
}
