<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class NavbarDropdownVisibilityTest extends TestCase
{
    public function test_navbars_do_not_clip_dropdowns_with_overflow_hidden(): void
    {
        $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));
        $adminNav = file_get_contents(resource_path('views/layouts/admin-navigation.blade.php'));

        $this->assertDoesNotMatchRegularExpression('/<nav\b[^>]*overflow-hidden/', $nav);
        $this->assertDoesNotMatchRegularExpression('/<nav\b[^>]*overflow-hidden/', $adminNav);
        $this->assertStringContainsString('<button type="button" class="user-glass-pill', $nav);
        $this->assertStringContainsString('<button type="button" class="user-glass-pill', $adminNav);
    }

    public function test_dropdown_component_supports_width_56_without_dark_classes(): void
    {
        $rendered = Blade::render('<x-dropdown align="right" width="56"><x-slot name="trigger"><button type="button">Menu</button></x-slot><x-slot name="content"><x-dropdown-link href="#">Profil</x-dropdown-link></x-slot></x-dropdown>');

        $this->assertStringContainsString('mt-2 w-56 ', $rendered);
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*(?<!-)\b56\b/', $rendered);
        $this->assertStringNotContainsString('dark:bg-gray-700', $rendered);
        $this->assertStringNotContainsString('dark:text-gray-300', $rendered);
    }
}
