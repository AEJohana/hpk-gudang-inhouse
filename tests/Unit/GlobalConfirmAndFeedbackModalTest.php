<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GlobalConfirmAndFeedbackModalTest extends TestCase
{
    /**
     * Test global confirmation modal component exists and has required features.
     */
    public function test_confirm_modal_component_structure(): void
    {
        $path = dirname(__DIR__, 2) . '/resources/views/components/confirm-modal.blade.php';
        $this->assertFileExists($path);

        $content = file_get_contents($path);

        // Alpine component and event listener
        $this->assertStringContainsString('globalConfirmModal()', $content);
        $this->assertStringContainsString('open-confirm-modal', $content);
        $this->assertStringContainsString('executeConfirm()', $content);

        // Dynamic form handling for POST / DELETE / GET
        $this->assertStringContainsString('x-ref="dynamicForm"', $content);
        $this->assertStringContainsString('x-ref="methodInput"', $content);
        $this->assertStringContainsString('@csrf', $content);

        // Global helper function
        $this->assertStringContainsString('window.showConfirmDialog', $content);
    }

    /**
     * Test toast notification component exists and has auto session flash and alpine listeners.
     */
    public function test_toast_notification_component_structure(): void
    {
        $path = dirname(__DIR__, 2) . '/resources/views/components/toast-notification.blade.php';
        $this->assertFileExists($path);

        $content = file_get_contents($path);

        // Alpine manager and event listener
        $this->assertStringContainsString('globalToastManager()', $content);
        $this->assertStringContainsString('x-on:toast.window', $content);
        $this->assertStringContainsString('addToast', $content);
        $this->assertStringContainsString('startTimer', $content);
        $this->assertStringContainsString('pauseToast', $content);
        $this->assertStringContainsString('resumeToast', $content);

        // Automatic Laravel session detection
        $this->assertStringContainsString("session('success')", $content);
        $this->assertStringContainsString("session('error')", $content);

        // Global helper function
        $this->assertStringContainsString('window.showToast', $content);
    }

    /**
     * Test milestone success modal component exists.
     */
    public function test_milestone_modal_component_structure(): void
    {
        $path = dirname(__DIR__, 2) . '/resources/views/components/milestone-modal.blade.php';
        $this->assertFileExists($path);

        $content = file_get_contents($path);

        $this->assertStringContainsString('globalMilestoneModal()', $content);
        $this->assertStringContainsString('open-milestone-modal', $content);
        $this->assertStringContainsString("session('milestone')", $content);
        $this->assertStringContainsString('window.showMilestoneModal', $content);
    }

    /**
     * Test app and admin layouts mount all feedback and confirmation components.
     */
    public function test_layouts_mount_feedback_components(): void
    {
        $appPath = dirname(__DIR__, 2) . '/resources/views/layouts/app.blade.php';
        $adminPath = dirname(__DIR__, 2) . '/resources/views/layouts/admin.blade.php';

        $appContent = file_get_contents($appPath);
        $adminContent = file_get_contents($adminPath);

        foreach ([$appContent, $adminContent] as $content) {
            $this->assertStringContainsString('<x-confirm-modal />', $content);
            $this->assertStringContainsString('<x-toast-notification />', $content);
            $this->assertStringContainsString('<x-milestone-modal />', $content);
        }
    }

    /**
     * Test that no native browser confirm() calls remain in views.
     */
    public function test_no_native_browser_confirm_in_views(): void
    {
        $viewsDir = dirname(__DIR__, 2) . '/resources/views';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsDir));

        $foundConfirms = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                // Look for confirm(' or confirm("
                if (preg_match('/(?<![a-zA-Z0-9_])confirm\s*\(/i', $content)) {
                    $foundConfirms[] = str_replace(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR, '', $file->getPathname());
                }
            }
        }

        $this->assertEmpty($foundConfirms, 'Found native confirm() in: ' . implode(', ', $foundConfirms));
    }
}
