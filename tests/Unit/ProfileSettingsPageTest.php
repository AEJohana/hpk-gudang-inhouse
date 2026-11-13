<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProfileSettingsPageTest extends TestCase
{
    /**
     * Test profile edit main view structure, executive user card, and shortcuts.
     */
    public function test_profile_edit_view_structure_and_executive_elements(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/profile/edit.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert back navigation to dashboard
        $this->assertStringContainsString("route('dashboard')", $content);
        $this->assertStringContainsString('fa-arrow-left', $content);

        // 2. Assert squircle avatar with pulse indicator
        $this->assertStringContainsString('$initials', $content);
        $this->assertStringContainsString('bg-emerald-500 border-2 border-white rounded-full', $content);

        // 3. Assert user model properties binding
        $this->assertStringContainsString('$user->name', $content);
        $this->assertStringContainsString('$user->email', $content);
        $this->assertStringContainsString('$user->role_badge', $content);
        $this->assertStringContainsString('$user->effective_department_name', $content);

        // 4. Assert quick navigation shortcuts exist
        $this->assertStringContainsString('href="#profil-info"', $content);
        $this->assertStringContainsString('href="#keamanan-sandi"', $content);
        $this->assertStringContainsString('href="#zona-bahaya"', $content);

        // 5. Assert all three partials are included
        $this->assertStringContainsString("@include('profile.partials.update-profile-information-form')", $content);
        $this->assertStringContainsString("@include('profile.partials.update-password-form')", $content);
        $this->assertStringContainsString("@include('profile.partials.delete-user-form')", $content);

        // 6. Assert dark boxy breeze default is replaced
        $this->assertStringNotContainsString('dark:bg-gray-800 shadow sm:rounded-lg', $content);
    }

    /**
     * Test profile information form tokens, fields, error bags, and UI.
     */
    public function test_update_profile_information_form_security_and_inputs(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/profile/partials/update-profile-information-form.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert CSRF & Method Spoofing
        $this->assertStringContainsString('@csrf', $content);
        $this->assertStringContainsString("@method('patch')", $content);
        $this->assertStringContainsString("route('profile.update')", $content);

        // 2. Assert Name & Email inputs with modern icons
        $this->assertStringContainsString('name="name"', $content);
        $this->assertStringContainsString('name="email"', $content);
        $this->assertStringContainsString('fa-regular fa-id-badge', $content);
        $this->assertStringContainsString('fa-regular fa-envelope', $content);

        // 3. Assert validation error bags
        $this->assertStringContainsString("\$errors->get('name')", $content);
        $this->assertStringContainsString("\$errors->get('email')", $content);

        // 4. Assert system access pills
        $this->assertStringContainsString('$user->role_badge', $content);
        $this->assertStringContainsString('$user->effective_department_name', $content);

        // 5. Assert submit button with icon
        $this->assertStringContainsString('fa-floppy-disk', $content);
        $this->assertStringContainsString('Simpan Perubahan', $content);
    }

    /**
     * Test password update form has Alpine.js show/hide toggle and validation bags.
     */
    public function test_update_password_form_alpine_toggle_and_security(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/profile/partials/update-password-form.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert Alpine.js visibility state
        $this->assertStringContainsString('showCurrent: false, showNew: false, showConfirm: false', $content);

        // 2. Assert CSRF & Method Spoofing
        $this->assertStringContainsString('@csrf', $content);
        $this->assertStringContainsString("@method('put')", $content);
        $this->assertStringContainsString("route('password.update')", $content);

        // 3. Assert all password fields with dynamic type binding
        $this->assertStringContainsString('name="current_password"', $content);
        $this->assertStringContainsString('name="password"', $content);
        $this->assertStringContainsString('name="password_confirmation"', $content);
        $this->assertStringContainsString(":type=\"showCurrent ? 'text' : 'password'\"", $content);
        $this->assertStringContainsString(":type=\"showNew ? 'text' : 'password'\"", $content);
        $this->assertStringContainsString(":type=\"showConfirm ? 'text' : 'password'\"", $content);

        // 4. Assert Eye icon toggle bindings
        $this->assertStringContainsString('fa-eye', $content);
        $this->assertStringContainsString('fa-eye-slash', $content);

        // 5. Assert password error bag
        $this->assertStringContainsString("\$errors->updatePassword->get('current_password')", $content);
        $this->assertStringContainsString("\$errors->updatePassword->get('password')", $content);
        $this->assertStringContainsString("\$errors->updatePassword->get('password_confirmation')", $content);

        // 6. Assert submit button
        $this->assertStringContainsString('Perbarui Kata Sandi', $content);
    }

    /**
     * Test danger zone delete user form modal, confirmation, and security tokens.
     */
    public function test_delete_user_form_modal_and_security(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/profile/partials/delete-user-form.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert CSRF & Method Spoofing
        $this->assertStringContainsString('@csrf', $content);
        $this->assertStringContainsString("@method('delete')", $content);
        $this->assertStringContainsString("route('profile.destroy')", $content);

        // 2. Assert modal trigger dispatch
        $this->assertStringContainsString("\$dispatch('open-modal', 'confirm-user-deletion')", $content);
        $this->assertStringContainsString('<x-modal name="confirm-user-deletion"', $content);

        // 3. Assert password confirmation input
        $this->assertStringContainsString('name="password"', $content);
        $this->assertStringContainsString("\$errors->userDeletion->get('password')", $content);

        // 4. Assert action buttons
        $this->assertStringContainsString('Batal', $content);
        $this->assertStringContainsString('Ya, Hapus Akun', $content);
    }
}
