<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    /**
     * Ensure default application settings exist.
     */
    public static function ensureDefaultSettingsExist(): void
    {
        if (self::count() > 0) {
            return;
        }

        $defaults = [
            ['key' => 'app_name', 'value' => 'WMS PT Hydraxle Perkasa', 'type' => 'string', 'group' => 'general', 'description' => 'Nama Aplikasi Gudang Inhouse'],
            ['key' => 'company_name', 'value' => 'PT Hydraxle Perkasa', 'type' => 'string', 'group' => 'general', 'description' => 'Nama Perusahaan Manufaktur'],
            ['key' => 'default_currency', 'value' => 'IDR', 'type' => 'string', 'group' => 'general', 'description' => 'Mata Uang Default'],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'group' => 'security', 'description' => 'Batas Percobaan Login Salah'],
            ['key' => 'enable_qr_scanner', 'value' => '1', 'type' => 'boolean', 'group' => 'general', 'description' => 'Aktifkan Scanner Kamera Barcode/QR'],
        ];

        foreach ($defaults as $setting) {
            self::create($setting);
        }
    }
}
