<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        \App\Models\Setting::ensureDefaultSettingsExist();
        $settings = \App\Models\Setting::orderBy('group')->get()->groupBy('group');
        $legacyWrCount = \App\Models\WorkRequest::where('wri_number', 'like', 'WR/%')->count();

        return view('admin.settings.index', compact('settings', 'legacyWrCount'));
    }

    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            \App\Models\Setting::where('key', $key)->update(['value' => $value]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan sistem berhasil disimpan.');
    }

    /**
     * Pemicu manual sinkronisasi data Work Request dari spreadsheet lama.
     */
    public function syncLegacyWr(Request $request)
    {
        \Illuminate\Support\Facades\Artisan::call('wr:migrate-legacy-spreadsheet');

        return redirect()->route('admin.settings.index')
            ->with('success', 'Sinkronisasi data Log WR Spreadsheet berhasil dieksekusi secara aman (Zero Duplicate).');
    }
}
