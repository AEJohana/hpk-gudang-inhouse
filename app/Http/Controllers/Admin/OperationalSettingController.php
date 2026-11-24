<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class OperationalSettingController extends Controller
{
    /**
     * Tampilkan halaman formulir Pengaturan Operasional.
     */
    public function index()
    {
        Setting::ensureDefaultSettingsExist();

        // Ambil atau buat setting wr_scan_required jika belum ada
        $wrScanSetting = Setting::firstOrCreate(
            ['key' => 'wr_scan_required'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'operational',
                'description' => 'Wajibkan Scan QR untuk Proses Pemesinan, MH & Penerimaan Gudang',
            ]
        );

        $operationalSettings = Setting::where('group', 'operational')->get();

        return view('admin.operational_settings.index', compact('wrScanSetting', 'operationalSettings'));
    }

    /**
     * Simpan pembaruan Pengaturan Operasional.
     */
    public function update(Request $request)
    {
        $settingsInput = $request->input('settings', []);

        // Tangani boolean switch wr_scan_required secara aman
        $wrScanRequired = isset($settingsInput['wr_scan_required']) && in_array($settingsInput['wr_scan_required'], ['1', 1, true, 'on']) ? '1' : '0';

        Setting::updateOrCreate(
            ['key' => 'wr_scan_required'],
            [
                'value' => $wrScanRequired,
                'type' => 'boolean',
                'group' => 'operational',
                'description' => 'Wajibkan Scan QR untuk Proses Pemesinan, MH & Penerimaan Gudang',
            ]
        );

        // Hapus cache
        Setting::clearSettingCache('wr_scan_required');

        $statusText = $wrScanRequired === '1' ? 'AKTIF (Wajib Scan QR)' : 'NONAKTIF (Klik Tombol Biasa)';
        ActivityLogger::system(
            'updated',
            "Memperbarui Pengaturan Operasional: Validasi Scan QR Work Request diatur ke {$statusText}.",
            null,
            ['wr_scan_required' => $wrScanRequired]
        );

        return redirect()->route('admin.operational-settings.index')
            ->with('success', "Pengaturan Operasional berhasil disimpan! Status Scan QR saat ini: {$statusText}.");
    }
}
