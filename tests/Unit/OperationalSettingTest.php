<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OperationalSettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->string('group')->default('general');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Test Setting::isWrScanRequired returns false when not found or set to '0'.
     */
    public function test_is_wr_scan_required_returns_false_when_disabled(): void
    {
        Setting::updateOrCreate(
            ['key' => 'wr_scan_required'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'operational',
                'description' => 'Wajib Scan QR Tiket WRI',
            ]
        );
        Setting::clearSettingCache('wr_scan_required');

        $this->assertFalse(Setting::isWrScanRequired());
    }

    /**
     * Test Setting::isWrScanRequired returns true when set to '1'.
     */
    public function test_is_wr_scan_required_returns_true_when_enabled(): void
    {
        Setting::updateOrCreate(
            ['key' => 'wr_scan_required'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'operational',
                'description' => 'Wajib Scan QR Tiket WRI',
            ]
        );
        Setting::clearSettingCache('wr_scan_required');

        $this->assertTrue(Setting::isWrScanRequired());
    }

    /**
     * Test Setting::clearSettingCache works properly.
     */
    public function test_clear_setting_cache(): void
    {
        Setting::updateOrCreate(
            ['key' => 'wr_scan_required'],
            ['value' => '0']
        );
        Setting::clearSettingCache('wr_scan_required');
        $this->assertFalse(Setting::isWrScanRequired());

        // Update directly in DB without clearing cache
        Setting::where('key', 'wr_scan_required')->update(['value' => '1']);
        
        // Still cached as false
        $this->assertFalse(Setting::isWrScanRequired());

        // Clear cache and check updated
        Setting::clearSettingCache('wr_scan_required');
        $this->assertTrue(Setting::isWrScanRequired());
    }

    /**
     * Test ActivityLogger user and system logging methods.
     */
    public function test_activity_logger_user_and_system_methods(): void
    {
        // Should not throw any exception even without active session or activity function
        \App\Services\ActivityLogger::user('updated', 'Test user action log');
        \App\Services\ActivityLogger::system('updated', 'Test system configuration log');

        $this->assertTrue(true);
    }
}
