<x-admin-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 240px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-gears me-1 text-[9px]"></i> KONFIGURASI
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Pengaturan Global
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div class="flex flex-col items-start gap-1 sm:gap-1.5">
                        <div>
                            <a href="{{ route('admin.dashboard') }}" 
                               class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0" 
                               title="Kembali ke Pusat Kendali Administrator">
                                <i class="fa-solid fa-arrow-left text-[11px] text-amber-300"></i>
                                <span>Kembali</span>
                            </a>
                        </div>
                        <h1 class="font-black text-xl sm:text-3xl text-white tracking-wide leading-tight">
                            Pengaturan Sistem & Keamanan
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. MAIN SETTINGS FORM CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf

                @foreach($settings as $group => $groupSettings)
                    @php
                        $groupMeta = match($group) {
                            'general' => [
                                'title' => 'Pengaturan Umum & Perusahaan',
                                'desc' => 'Identitas aplikasi, nama perusahaan, dan mata uang transaksi',
                                'icon' => 'fa-building text-amber-600 bg-amber-50',
                            ],
                            'security' => [
                                'title' => 'Keamanan & Autentikasi',
                                'desc' => 'Kebijakan login, percobaan password salah, dan registrasi publik',
                                'icon' => 'fa-lock text-rose-600 bg-rose-50',
                            ],
                            default => [
                                'title' => 'Pengaturan ' . ucfirst($group),
                                'desc' => 'Parameter konfigurasi grup ' . $group,
                                'icon' => 'fa-sliders text-teal-600 bg-teal-50',
                            ],
                        };
                    @endphp
                    <div class="p-6 sm:p-8 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                        <div class="flex items-center space-x-3 mb-6 pb-4 border-b border-slate-100">
                            <div class="w-10 h-10 rounded-xl {{ $groupMeta['icon'] }} flex items-center justify-center font-bold text-sm shadow-xs">
                                <i class="fa-solid {{ explode(' ', $groupMeta['icon'])[0] }}"></i>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-slate-900 text-base">{{ $groupMeta['title'] }}</h3>
                                <p class="text-xs text-slate-500">{{ $groupMeta['desc'] }}</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            @foreach($groupSettings as $setting)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:items-center">
                                    <!-- Label & Deskripsi -->
                                    <div class="md:col-span-1">
                                        <label for="setting_{{ $setting->key }}" class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                                            {{ $setting->description ?? ucwords(str_replace('_', ' ', $setting->key)) }}
                                        </label>
                                        <span class="text-[10px] text-slate-400 font-mono block mt-0.5">key: {{ $setting->key }}</span>
                                    </div>

                                    <!-- Input Field -->
                                    <div class="md:col-span-2">
                                        @if($setting->type == 'boolean')
                                            <select name="settings[{{ $setting->key }}]" 
                                                    id="setting_{{ $setting->key }}" 
                                                    class="w-full md:w-64 px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                                                <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>Aktif (Diizinkan / Ya)</option>
                                                <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>Nonaktif (Dilarang / Tidak)</option>
                                            </select>
                                        @elseif($setting->type == 'integer')
                                            <div class="relative w-full md:w-64">
                                                <input type="number" 
                                                       name="settings[{{ $setting->key }}]" 
                                                       id="setting_{{ $setting->key }}" 
                                                       value="{{ $setting->value }}" 
                                                       class="w-full px-3.5 py-2.5 text-xs font-mono rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                                            </div>
                                        @else
                                            <input type="text" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   id="setting_{{ $setting->key }}" 
                                                   value="{{ $setting->value }}" 
                                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- Action Footer -->
                <div class="bg-slate-50 px-8 py-4 flex items-center justify-between border-t border-slate-200">
                    <span class="text-xs text-slate-500">Perubahan konfigurasi disimpan langsung ke database</span>
                    <button type="submit" 
                            class="px-6 py-2.5 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        <!-- 3. DATA MIGRATION & SYNC TOOL CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg text-emerald-600 bg-emerald-50 border border-emerald-100 shadow-2xs shrink-0">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-800">Sinkronisasi Log WR Spreadsheet (Sistem Lama)</h2>
                        <p class="text-xs text-slate-400">Migrasi & sinkronisasi berkas Form Work Request - Log WR.csv ke sistem WMS</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200/60">
                        <i class="fa-solid fa-database text-[10px]"></i>
                        {{ number_format($legacyWrCount ?? 0) }} Tiket Terarsip
                    </span>
                </div>
            </div>

            <div class="p-8 space-y-6">
                <div class="bg-amber-50/70 border border-amber-200/60 rounded-xl p-4 text-xs text-amber-900 space-y-2">
                    <div class="font-bold flex items-center gap-2 text-amber-800">
                        <i class="fa-solid fa-shield-halved text-amber-600"></i>
                        Protokol Keamanan Data & Stok (Zero Stock Disturbance)
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li><strong>Zero Duplicate:</strong> Nomor WR lama yang sudah terdaftar akan otomatis dilewati tanpa menduplikasi data.</li>
                        <li><strong>Zero Stock Disturbance:</strong> Migrasi ini murni mencatat riwayat tiket & antrean mesin, tanpa memodifikasi saldo stok master atau mutasi inventaris.</li>
                        <li><strong>Status Terjaga:</strong> Tiket yang sedang berjalan di mesin/stasiun lama tetap aktif dan dapat diproses melalui antarmuka saat ini.</li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
                    <div class="text-xs text-slate-500">
                        <i class="fa-solid fa-circle-info text-slate-400 me-1"></i>
                        Dokumentasi lengkap alur & histori migrasi tercatat di <span class="font-mono font-semibold text-slate-700">docs/MIGRASI_LOG_WR_SPREADSHEET.md</span>
                    </div>
                    <form action="{{ route('admin.settings.sync-legacy-wr') }}" method="POST" onsubmit="return confirm('Mulai sinkronisasi data Work Request dari spreadsheet lama? Proses ini aman dan tidak akan merubah stok atau menduplikasi data.');">
                        @csrf
                        <button type="submit" 
                                class="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                            <i class="fa-solid fa-arrows-rotate"></i>
                            Sinkronkan Data WR Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
