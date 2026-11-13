<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class QrRequestCenteredDockTest extends TestCase
{
    /**
     * Test QR request queue has centered floating control dock with tabs and search.
     */
    public function test_qr_request_index_has_centered_control_dock(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/qr_requests/index.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert centered dock container exists
        $this->assertStringContainsString('flex justify-center w-full my-2', $content);
        $this->assertStringContainsString('bg-white/95 backdrop-blur-md rounded-2xl md:rounded-full', $content);

        // 2. Assert segmented tabs exist with count badges
        $this->assertStringContainsString('Menunggu Dicetak', $content);
        $this->assertStringContainsString('Selesai Dicetak', $content);
        $this->assertStringContainsString('Semua Antrian', $content);
        $this->assertStringContainsString('counts->pending', $content);
        $this->assertStringContainsString('counts->printed', $content);
        $this->assertStringContainsString('counts->total', $content);

        // 3. Assert subtle divider exists
        $this->assertStringContainsString('hidden md:block w-px h-6 bg-slate-200/90 mx-1', $content);

        // 4. Assert integrated search capsule exists inside the dock
        $this->assertStringContainsString('action="{{ route(\'qr-requests.index\') }}"', $content);
        $this->assertStringContainsString('name="q"', $content);
        $this->assertStringContainsString('Cari', $content);
    }
}
