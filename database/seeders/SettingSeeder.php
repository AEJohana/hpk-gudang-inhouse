<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'WMS HPK In-House', 'type' => 'string', 'group' => 'general', 'description' => 'Nama Aplikasi'],
            ['key' => 'company_name', 'value' => 'PT Hydraxle Perkasa Karoseri', 'type' => 'string', 'group' => 'general', 'description' => 'Nama Perusahaan'],
            ['key' => 'enable_registration', 'value' => '0', 'type' => 'boolean', 'group' => 'security', 'description' => 'Izinkan registrasi publik'],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'group' => 'security', 'description' => 'Batas percobaan login gagal'],
            ['key' => 'default_currency', 'value' => 'IDR', 'type' => 'string', 'group' => 'general', 'description' => 'Mata Uang Default'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
