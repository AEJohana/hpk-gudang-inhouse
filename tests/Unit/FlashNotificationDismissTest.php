<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FlashNotificationDismissTest extends TestCase
{
    /**
     * Test app layout and toast notification component have auto-dismiss timer and dismiss button.
     */
    public function test_app_layout_contains_auto_dismiss_flash_notification(): void
    {
        $appBladePath = dirname(__DIR__, 2) . '/resources/views/layouts/app.blade.php';
        $toastBladePath = dirname(__DIR__, 2) . '/resources/views/components/toast-notification.blade.php';
        
        $this->assertFileExists($appBladePath);
        $this->assertFileExists($toastBladePath);

        $appContent = file_get_contents($appBladePath);
        $toastContent = file_get_contents($toastBladePath);

        // App layout mounts global toast notification component
        $this->assertStringContainsString("<x-toast-notification />", $appContent);

        // Toast component has auto-dismiss timer, progress countdown, and close button
        $this->assertStringContainsString("globalToastManager()", $toastContent);
        $this->assertStringContainsString("startTimer(toast)", $toastContent);
        $this->assertStringContainsString("@click=\"removeToast(toast.id)\"", $toastContent);
        $this->assertStringContainsString("fa-solid fa-xmark", $toastContent);
    }

    /**
     * Test admin layout and work requests show view contain notifications.
     */
    public function test_admin_and_show_views_contain_auto_dismiss(): void
    {
        $adminPath = dirname(__DIR__, 2) . '/resources/views/layouts/admin.blade.php';
        $showPath = dirname(__DIR__, 2) . '/resources/views/work_requests/show.blade.php';

        $this->assertFileExists($adminPath);
        $this->assertFileExists($showPath);

        $adminContent = file_get_contents($adminPath);
        $showContent = file_get_contents($showPath);

        // Admin layout mounts global toast notification component
        $this->assertStringContainsString("<x-toast-notification />", $adminContent);

        // Work requests show view has notification handling
        $this->assertStringContainsString("setTimeout(() => show = false, 3500)", $showContent);
        $this->assertStringContainsString("@click=\"show = false\"", $showContent);
    }
}
