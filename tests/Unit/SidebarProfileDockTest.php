<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SidebarProfileDockTest extends TestCase
{
    /**
     * Test app layout sidebar has executive user profile dock at bottom.
     */
    public function test_app_sidebar_has_executive_user_profile_dock(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/layouts/app.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert old top user card is removed
        $this->assertStringNotContainsString('class="sidebar-user-card"', $content);

        // 2. Assert new profile dock exists with avatar pulse indicator
        $this->assertStringContainsString('Pengguna Aktif (Online)', $content);
        $this->assertStringContainsString('bg-emerald-500 border-2 border-white rounded-full', $content);

        // 3. Assert user info bindings exist
        $this->assertStringContainsString('Auth::user()->name', $content);
        $this->assertStringContainsString('Auth::user()->role_badge', $content);

        // 4. Assert quick profile gear button exists
        $this->assertStringContainsString('route(\'profile.edit\')', $content);
        $this->assertStringContainsString('fa-gear', $content);

        // 5. Assert full-width logout button exists
        $this->assertStringContainsString('route(\'logout\')', $content);
        $this->assertStringContainsString('fa-arrow-right-from-bracket', $content);
        $this->assertStringContainsString('Keluar', $content);

        // 6. Assert company branding footer exists
        $this->assertStringContainsString('PT. Hydraxle Perkasa', $content);
    }

    /**
     * Test admin layout sidebar also has matching executive user profile dock.
     */
    public function test_admin_sidebar_has_matching_profile_dock(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/layouts/admin.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert old top user card is removed
        $this->assertStringNotContainsString('class="sidebar-user-card"', $content);

        // 2. Assert dock and actions exist
        $this->assertStringContainsString('Pengguna Aktif (Online)', $content);
        $this->assertStringContainsString('route(\'profile.edit\')', $content);
        $this->assertStringContainsString('route(\'logout\')', $content);
        $this->assertStringContainsString('PT. Hydraxle Perkasa', $content);
    }
}
