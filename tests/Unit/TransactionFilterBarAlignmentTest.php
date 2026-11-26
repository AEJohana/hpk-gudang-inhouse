<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TransactionFilterBarAlignmentTest extends TestCase
{
    /**
     * Test transaction index filter bar is aligned horizontally and uses live auto-filter.
     */
    public function test_transaction_filter_bar_is_aligned_and_autosubmits(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/resources/views/transactions/index.blade.php';
        $this->assertFileExists($viewPath);

        $content = file_get_contents($viewPath);

        // 1. Assert horizontal alignment layout & live filter attributes
        $this->assertStringContainsString('data-live-filter', $content);
        $this->assertStringContainsString('data-live-target="#transactions-results"', $content);
        $this->assertStringContainsString("@fragment('results')", $content);
        $this->assertStringNotContainsString('grid grid-cols-1 sm:grid-cols-12', $content);

        // 2. Assert no legacy full-reload onchange or obsolete Filter button
        $this->assertStringContainsString('name="type"', $content);
        $this->assertStringNotContainsString('onchange="this.form.submit()"', $content);
        $this->assertStringNotContainsString('title="Terapkan Filter Transaksi"', $content);

        // 3. Assert search input and live reset/clear controls are present
        $this->assertStringContainsString('name="search"', $content);
        $this->assertStringContainsString('data-live-reset', $content);
    }

    /**
     * Test dropdown and search filters in Master Components, Users, and Audit Logs use unified live auto-filter.
     */
    public function test_other_select_filters_autosubmit_on_select(): void
    {
        // Master Components
        $componentsView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/master_components/index.blade.php');
        $this->assertStringContainsString('name="category"', $componentsView);
        $this->assertStringContainsString('data-live-filter', $componentsView);
        $this->assertStringContainsString("@fragment('results')", $componentsView);
        $this->assertStringNotContainsString('onchange="this.form.submit()"', $componentsView);

        // Admin Users
        $usersView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/users/index.blade.php');
        $this->assertStringContainsString('name="role"', $usersView);
        $this->assertStringContainsString('name="work_area"', $usersView);
        $this->assertStringContainsString('name="department_id"', $usersView);
        $this->assertStringContainsString('data-live-filter', $usersView);
        $this->assertStringContainsString("@fragment('results')", $usersView);
        $this->assertStringNotContainsString('onchange="this.form.submit()"', $usersView);

        // Admin Audit Logs
        $auditLogsView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/admin/audit_logs/index.blade.php');
        $this->assertStringContainsString('name="period"', $auditLogsView);
        $this->assertStringContainsString('name="module"', $auditLogsView);
        $this->assertStringContainsString('name="user_id"', $auditLogsView);
        $this->assertStringContainsString('data-live-filter', $auditLogsView);
        $this->assertStringContainsString("@fragment('results')", $auditLogsView);
        $this->assertStringNotContainsString('onchange="this.form.submit()"', $auditLogsView);
    }
}
