<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class LoginPasswordToggleTest extends TestCase
{
    /**
     * Test login view contains interactive show/hide password toggle markup without clipboard copy.
     */
    public function test_login_blade_contains_password_reveal_toggle(): void
    {
        $loginBladePath = dirname(__DIR__, 2) . '/resources/views/auth/login.blade.php';
        $this->assertFileExists($loginBladePath);

        $content = file_get_contents($loginBladePath);

        // Alpine state
        $this->assertStringContainsString("showPassword: false", $content);

        // Dynamic input type
        $this->assertStringContainsString(":type=\"showPassword ? 'text' : 'password'\"", $content);

        // Toggle click handler
        $this->assertStringContainsString("@click=\"togglePassword()\"", $content);

        // Icon classes
        $this->assertStringContainsString("fa-solid fa-eye", $content);
        $this->assertStringContainsString("fa-solid fa-eye-slash", $content);

        // Tactile pill container
        $this->assertStringContainsString("w-8 h-8 rounded-lg", $content);

        // Countdown line indicator SVG
        $this->assertStringContainsString("stroke-dasharray=\"100\"", $content);
        $this->assertStringContainsString("pathLength=\"100\"", $content);
        $this->assertStringContainsString("stroke=\"#22c55e\"", $content);

        // Pastel color states (Lampiran 3 & 4)
        $this->assertStringContainsString("bg-[#EEF0FE]", $content);
        $this->assertStringContainsString("bg-[#E8FAF0]", $content);

        // Security check: must NOT have clipboard copy in login view
        $this->assertStringNotContainsString("navigator.clipboard", $content);
    }
}
