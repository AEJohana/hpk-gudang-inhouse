<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GlobalScannerSafetyTest extends TestCase
{
    public function test_layouts_app_has_fail_safe_scanner_closing_and_error_notice(): void
    {
        $appPath = realpath(__DIR__ . '/../../resources/views/layouts/app.blade.php');
        $this->assertNotFalse($appPath);
        $content = file_get_contents($appPath);

        // 1. Modal close must set scannerOpen = false immediately before or during teardown
        $this->assertMatchesRegularExpression(
            '/closeScanner\(\)\s*\{\s*this\.scannerOpen\s*=\s*false;/i',
            $content,
            'closeScanner() must set scannerOpen = false before stopping camera to guarantee modal dismissal'
        );

        // 2. stopCameraScanner must safely check scanner state and catch synchronous/async exceptions
        $this->assertStringContainsString('stopCameraScanner()', $content);
        $this->assertStringContainsString('scanner.getState()', $content);
        $this->assertStringContainsString('Silently handled scanner stop', $content);

        // 3. Must contain camera error message notice for user guidance
        $this->assertStringContainsString('x-show="cameraErrorMessage"', $content);
        $this->assertStringContainsString('x-text="cameraErrorMessage"', $content);
    }

    public function test_layouts_admin_has_fail_safe_scanner_closing_and_error_notice(): void
    {
        $adminPath = realpath(__DIR__ . '/../../resources/views/layouts/admin.blade.php');
        $this->assertNotFalse($adminPath);
        $content = file_get_contents($adminPath);

        $this->assertMatchesRegularExpression(
            '/closeScanner\(\)\s*\{\s*this\.scannerOpen\s*=\s*false;/i',
            $content,
            'Admin closeScanner() must set scannerOpen = false before stopping camera'
        );

        $this->assertStringContainsString('stopCameraScanner()', $content);
        $this->assertStringContainsString('scanner.getState()', $content);
        $this->assertStringContainsString('Silently handled scanner stop', $content);

        $this->assertStringContainsString('x-show="cameraErrorMessage"', $content);
        $this->assertStringContainsString('x-text="cameraErrorMessage"', $content);
    }

    public function test_material_handling_scanner_safely_closes_and_cleans_up(): void
    {
        $mhPath = realpath(__DIR__ . '/../../resources/views/work_station_supplies/index.blade.php');
        $this->assertNotFalse($mhPath);
        $content = file_get_contents($mhPath);

        $this->assertMatchesRegularExpression(
            '/closeMhScanner\(\)\s*\{\s*this\.mhScannerOpen\s*=\s*false;/i',
            $content,
            'closeMhScanner() must set mhScannerOpen = false before stopping camera'
        );

        $this->assertStringContainsString('stopCameraMh()', $content);
        $this->assertStringContainsString('Silently handled MH scanner stop', $content);
    }

    public function test_transaction_create_scanner_safely_stops(): void
    {
        $txPath = realpath(__DIR__ . '/../../resources/views/transactions/create.blade.php');
        $this->assertNotFalse($txPath);
        $content = file_get_contents($txPath);

        $this->assertStringContainsString('stopCamera()', $content);
        $this->assertStringContainsString('Tx camera stop error', $content);
    }
}
