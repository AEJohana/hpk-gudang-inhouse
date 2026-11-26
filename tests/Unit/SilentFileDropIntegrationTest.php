<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SilentFileDropIntegrationTest extends TestCase
{
    public function test_silent_file_drop_module_is_wired_in_app_js_and_all_seven_upload_views(): void
    {
        $root = dirname(__DIR__, 2);

        $jsPath = $root . '/resources/js/components/silent-file-drop.js';
        $this->assertFileExists($jsPath);
        $jsContent = file_get_contents($jsPath);
        $this->assertStringContainsString('initSilentFileDrops', $jsContent);
        $this->assertStringContainsString('cleanupSilentFileDrops', $jsContent);
        $this->assertStringContainsString('new DataTransfer()', $jsContent);

        $appJsContent = file_get_contents($root . '/resources/js/app.js');
        $this->assertStringContainsString('initSilentFileDrops()', $appJsContent);
        $this->assertStringContainsString('cleanupSilentFileDrops()', $appJsContent);

        $uploadViews = [
            'resources/views/work_requests/create.blade.php',
            'resources/views/master_components/create.blade.php',
            'resources/views/master_components/edit.blade.php',
            'resources/views/ecrs/create.blade.php',
            'resources/views/disposals/create.blade.php',
            'resources/views/admin/users/create.blade.php',
            'resources/views/admin/users/edit.blade.php',
        ];

        foreach ($uploadViews as $relativeView) {
            $viewContent = file_get_contents($root . '/' . $relativeView);
            $this->assertStringContainsString('data-drop-zone', $viewContent, "Missing data-drop-zone in {$relativeView}");
        }
    }
}
