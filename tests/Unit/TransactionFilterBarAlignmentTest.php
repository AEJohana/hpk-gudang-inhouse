<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TransactionFilterBarAlignmentTest extends TestCase
{
    /**
     * Test transaction index filter bar is aligned horizontally and auto-submits on select.
     */
    public function test_transaction_filter_bar_is_aligned_and_autosubmits(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/transactions/index.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert horizontal alignment layout
        $this->assertStringContainsString('class="flex items-center gap-2 w-full"', $content);
        $this->assertStringNotContainsString('grid grid-cols-1 sm:grid-cols-12', $content);

        // 2. Assert auto-submit on change for type select
        $this->assertStringContainsString('name="type"', $content);
        $this->assertStringContainsString('onchange="this.form.submit()"', $content);

        // 3. Assert search input and action buttons are present
        $this->assertStringContainsString('name="search"', $content);
        $this->assertStringContainsString('title="Terapkan Filter Transaksi"', $content);
    }

    /**
     * Test dropdown filters in Master Components, Users, and Audit Logs also auto-submit on select.
     */
    public function test_other_select_filters_autosubmit_on_select(): void
    {
        // Master Components
        $componentsView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/master_components/index.blade.php');
        $this->assertStringContainsString('name="category"', $componentsView);
        $this->assertStringContainsString('onchange="this.form.submit()"', $componentsView);

        // Admin Users
        $usersView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/users/index.blade.php');
        $this->assertStringContainsString('name="role"', $usersView);
        $this->assertStringContainsString('name="work_area"', $usersView);
        $this->assertStringContainsString('name="department_id"', $usersView);
        $this->assertEquals(3, substr_count($usersView, 'onchange="this.form.submit()"'));

        // Admin Audit Logs
        $auditLogsView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/audit_logs/index.blade.php');
        $this->assertStringContainsString('name="period"', $auditLogsView);
        $this->assertStringContainsString('name="module"', $auditLogsView);
        $this->assertStringContainsString('name="user_id"', $auditLogsView);
        $this->assertEquals(3, substr_count($auditLogsView, 'onchange="this.form.submit()"'));
    }
}
