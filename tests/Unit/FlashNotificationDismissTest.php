<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FlashNotificationDismissTest extends TestCase
{
    /**
     * Test app layout flash message banner has auto-dismiss timer and dismiss button.
     */
    public function test_app_layout_contains_auto_dismiss_flash_notification(): void
    {
        $appBladePath = dirname(__DIR__, 2) . '/resources/views/layouts/app.blade.php';
        $this->assertFileExists($appBladePath);

        $content = file_get_contents($appBladePath);

        // Auto-dismiss timer
        $this->assertStringContainsString("x-init=\"setTimeout(() => show = false, 3500)\"", $content);

        // Smooth transition
        $this->assertStringContainsString("x-transition:leave=\"transition ease-in duration-300 transform\"", $content);

        // Close button
        $this->assertStringContainsString("@click=\"show = false\"", $content);
        $this->assertStringContainsString("fa-solid fa-xmark", $content);
    }

    /**
     * Test admin layout and work requests show view contain auto-dismiss flash notification.
     */
    public function test_admin_and_show_views_contain_auto_dismiss(): void
    {
        $adminPath = dirname(__DIR__, 2) . '/resources/views/layouts/admin.blade.php';
        $showPath = dirname(__DIR__, 2) . '/resources/views/work_requests/show.blade.php';

        $this->assertFileExists($adminPath);
        $this->assertFileExists($showPath);

        $adminContent = file_get_contents($adminPath);
        $showContent = file_get_contents($showPath);

        $this->assertStringContainsString("setTimeout(() => show = false, 3500)", $adminContent);
        $this->assertStringContainsString("@click=\"show = false\"", $adminContent);

        $this->assertStringContainsString("setTimeout(() => show = false, 3500)", $showContent);
        $this->assertStringContainsString("@click=\"show = false\"", $showContent);
    }
}
