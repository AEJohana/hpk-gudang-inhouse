<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header !h-[240px] sm:!h-[270px]">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-5 sm:pt-6 pb-10 sm:pb-12 px-4 sm:px-6 lg:px-8">
                <!-- Top Badge Row -->
                <div class="flex items-center space-x-2.5 mb-2.5 sm:mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-shield-halved me-1 text-[9px]"></i> SUPER ADMIN
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        PT Hydraxle Perkasa Karoseri
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center space-x-3 min-w-0">
                        <a href="{{ route('dashboard') }}" 
                           class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 flex items-center justify-center transition shadow-2xs shrink-0 cursor-pointer hover-spring" 
                           title="Kembali ke Beranda WMS Utama">
                            <i class="fa-solid fa-arrow-left text-sm text-amber-300"></i>
                        </a>
                        <h1 class="gsap-reveal-title font-black text-xl sm:text-2xl lg:text-3xl text-white tracking-wide leading-tight truncate">
                            Pusat Kendali Administrator
                        </h1>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center space-x-2.5 ml-auto">
                        <span class="inline-flex items-center text-xs text-white/90 font-medium bg-white/10 border border-white/10 px-3.5 py-1.5 rounded-full backdrop-blur-xs">
                            <i class="fa-regular fa-calendar-days text-amber-300 me-2"></i>
                            {{ now()->translatedFormat('l, d F Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. PRIMARY STATS ROW (DASHBOARD-GRID) -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="gsap-reveal-title font-bold text-slate-900 text-lg">Ringkasan Sistem & Master Data</h3>
                    <p class="text-xs text-slate-500">Statistik real-time entitas master dan keamanan aplikasi</p>
                </div>
            </div>

            <div class="dashboard-grid gsap-stagger-cards">
                <!-- 1. Pengguna & Otorisasi -->
                <a href="{{ route('admin.users.index') }}" class="dash-card group hover-spring">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-users-gear card-icon-bg"></i>
                    <div class="icon-box text-amber-600 bg-amber-50">
                        <i class="fa-solid fa-users-gear"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-amber-600 transition">Manajemen Pengguna & Otorisasi</h4>
                            <span class="text-2xl font-black text-slate-900"><span class="counter-value">{{ $usersCount ?? \App\Models\User::count() }}</span></span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Akun operasional gudang, stasiun mesin, approval & hak akses 64 izin modul</p>
                    </div>
                </a>

                <!-- 2. Kategori Komponen -->
                <a href="{{ route('admin.component-categories.index') }}" class="dash-card group hover-spring">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-tags card-icon-bg"></i>
                    <div class="icon-box text-teal-600 bg-teal-50">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-teal-600 transition">Kategori Komponen</h4>
                            <span class="text-2xl font-black text-slate-900"><span class="counter-value">{{ $categoriesCount ?? \App\Models\ComponentCategory::count() }}</span></span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Kelompok hidrolik, raw material baja, fastener & cat</p>
                    </div>
                </a>

                <!-- 4. Audit Log -->
                <a href="{{ route('admin.audit-logs.index') }}" class="dash-card group hover-spring">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-clipboard-list card-icon-bg"></i>
                    <div class="icon-box text-rose-600 bg-rose-50">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-rose-600 transition">Audit Log Aktivitas</h4>
                            <span class="text-2xl font-black text-slate-900"><span class="counter-value">{{ $logsCount ?? \Spatie\Activitylog\Models\Activity::count() }}</span></span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Pencatatan riwayat perubahan data & keamanan sistem</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- 2B. NAS MASTER STORAGE & PHOTO STREAMING GATEWAY -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-slate-50/80 to-white flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-lg text-indigo-600 bg-indigo-50 border border-indigo-100 shadow-2xs shrink-0">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-extrabold text-slate-900 text-base">Server NAS & Master Gambar Komponen (Drive Z:)</h3>
                            @if($storageStatus['is_nas_connected'] ?? false)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
                                    ONLINE / TERHUBUNG
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 me-1.5"></span>
                                    OFFLINE / TERPUTUS
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 font-mono text-[11px] truncate max-w-2xl">
                            {{ $storageStatus['primary_dir'] ?? 'Z:\08.Project\04. HPK Digitalisasi Program\04. Gudang-Komponen\Komonpen Gudang In House' }}
                        </p>
                    </div>
                </div>

                <!-- Jalur Konektivitas Tag -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ ($storageStatus['primary_connected'] ?? false) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                        <i class="fa-solid fa-hard-drive me-1.5 text-[11px]"></i>
                        Drive Z: {{ ($storageStatus['primary_connected'] ?? false) ? 'Aktif' : 'Tertidur/Sleep' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ ($storageStatus['unc_connected'] ?? false) ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                        <i class="fa-solid fa-network-wired me-1.5 text-[11px]"></i>
                        UNC IP 192.168.1.150: {{ ($storageStatus['unc_connected'] ?? false) ? 'Aktif' : 'Offline' }}
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- 4 Metrics Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Metric 1: Berkas di NAS -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Master NAS (Z:)</span>
                            <i class="fa-solid fa-folder-open text-indigo-500"></i>
                        </div>
                        <div class="text-2xl font-black text-slate-900">
                            {{ number_format($storageStatus['nas_total_files'] ?? 0) }}
                            <span class="text-xs font-semibold text-slate-400">Berkas</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Sumber acuan resmi engineering</p>
                    </div>

                    <!-- Metric 2: Webroot Mirror (SSD) -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Webroot SSD Mirror</span>
                            <i class="fa-solid fa-bolt text-amber-500"></i>
                        </div>
                        <div class="text-2xl font-black text-slate-900">
                            {{ number_format($storageStatus['mirror_files_count'] ?? 0) }}
                            <span class="text-xs font-semibold text-slate-400">Berkas</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Pemuatan instan lokal 0.01 ms</p>
                    </div>

                    <!-- Metric 3: Selisih Belum Tersinkron -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Belum Tercermin</span>
                            <i class="fa-solid fa-cloud-arrow-down text-teal-500"></i>
                        </div>
                        <div class="text-2xl font-black {{ ($storageStatus['pending_sync_count'] ?? 0) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                            {{ number_format($storageStatus['pending_sync_count'] ?? 0) }}
                            <span class="text-xs font-semibold text-slate-400">Berkas</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            {{ ($storageStatus['pending_sync_count'] ?? 0) > 0 ? 'Langsung di-stream dari Z:' : '100% Tercermin di SSD' }}
                        </p>
                    </div>

                    <!-- Metric 4: Total Master Komponen -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Katalog Master Part</span>
                            <i class="fa-solid fa-cubes text-blue-500"></i>
                        </div>
                        <div class="text-2xl font-black text-slate-900">
                            {{ number_format($storageStatus['total_components'] ?? 0) }}
                            <span class="text-xs font-semibold text-slate-400">Item</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Master komponen terverifikasi</p>
                    </div>
                </div>

                <!-- Info Banner & Interactive Action Buttons -->
                <div class="bg-indigo-50/60 border border-indigo-200/70 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1 text-xs text-indigo-950">
                        <div class="font-bold flex items-center gap-1.5 text-indigo-900">
                            <i class="fa-solid fa-circle-info text-indigo-600"></i>
                            Pusat Streaming Gambar Teknik & CAD PDF
                        </div>
                        <p class="text-slate-600 leading-relaxed">
                            Sistem secara bawaan memprioritaskan pembacaan gambar langsung dari folder master <span class="font-mono font-semibold text-slate-800">Z:\...</span> dengan <span class="font-semibold text-indigo-700">In-Memory Indexing</span> super cepat (4 ms). Anda juga dapat mencerminkan seluruh foto ke disk lokal untuk kecepatan maksimum di seluruh intranet pabrik.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <!-- Form 1: Reset Cache Resolusi Foto -->
                        <form id="clear-photo-cache-form" action="{{ route('admin.clear-photo-cache') }}" method="POST">
                            @csrf
                            <button type="button" 
                                    @click="$dispatch('open-confirm-modal', {
                                        title: 'Reset Cache Foto Komponen',
                                        message: 'Bersihkan cache resolusi foto komponen? Sistem akan membaca ulang daftar berkas fisik langsung dari folder Z: / NAS.',
                                        confirmText: 'Ya, Bersihkan Cache',
                                        cancelText: 'Batal',
                                        type: 'info',
                                        icon: 'fa-solid fa-broom',
                                        formId: 'clear-photo-cache-form'
                                    })"
                                    class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 font-bold text-xs border border-slate-300 rounded-xl shadow-2xs transition flex items-center gap-1.5 cursor-pointer"
                                    title="Gunakan jika baru saja menyalin file gambar baru ke folder Z:">
                                <i class="fa-solid fa-broom text-slate-400"></i>
                                <span>Reset Cache Foto</span>
                            </button>
                        </form>

                        <!-- Form 2: Sinkronkan ke Webroot Mirror -->
                        <form id="sync-nas-photos-form" action="{{ route('admin.sync-photos') }}" method="POST">
                            @csrf
                            <button type="button" 
                                    @click="$dispatch('open-confirm-modal', {
                                        title: 'Sinkronisasi Foto NAS ke Webroot',
                                        message: 'Mulai menyinkronkan seluruh berkas gambar dari Server NAS (Z:) ke penyimpanan SSD lokal webroot? Proses ini aman dan tidak mengganggu stok atau data master.',
                                        confirmText: 'Ya, Mulai Sinkronisasi',
                                        cancelText: 'Batal',
                                        type: 'info',
                                        icon: 'fa-solid fa-arrows-rotate',
                                        formId: 'sync-nas-photos-form'
                                    })"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2 cursor-pointer">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                <span>Sinkronkan ke Webroot Mirror</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. SECONDARY WORKSPACE GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Antrian Persetujuan & Tindakan Cepat -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base flex items-center">
                                <i class="fa-solid fa-list-check text-amber-500 me-2"></i>
                                Antrian Verifikasi Lintas Divisi
                            </h3>
                            <p class="text-xs text-slate-500">Persetujuan dokumen engineering dan afkir material yang perlu ditindaklanjuti</p>
                        </div>
                        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                            Pusat Review
                        </span>
                    </div>

                    <div class="space-y-3">
                        <!-- ECR Pending -->
                        <a href="{{ route('ecrs.index') }}" class="flex items-center justify-between p-3.5 bg-slate-50 hover:bg-purple-50/70 border border-slate-200/80 hover:border-purple-200 rounded-xl transition group">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-file-pen"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-purple-900 transition">Engineering Change Requests (ECR)</h4>
                                    <p class="text-xs text-slate-500">Revisi spek komponen dump & tangki menunggu tinjauan</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-1 text-xs font-black rounded-full {{ ($pendingEcrsCount ?? 0) > 0 ? 'bg-purple-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $pendingEcrsCount ?? 0 }} Pending
                                </span>
                                <i class="fa-solid fa-chevron-right text-slate-400 group-hover:text-purple-600 text-xs transition"></i>
                            </div>
                        </a>

                        <!-- Disposal Pending -->
                        <a href="{{ route('disposals.index') }}" class="flex items-center justify-between p-3.5 bg-slate-50 hover:bg-rose-50/70 border border-slate-200/80 hover:border-rose-200 rounded-xl transition group">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-trash-can"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-rose-900 transition">Pengajuan Afkir & Disposal Material</h4>
                                    <p class="text-xs text-slate-500">Penghapusan scrap pelat besi, part rusak & drum afkir</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-1 text-xs font-black rounded-full {{ ($pendingDisposalsCount ?? 0) > 0 ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $pendingDisposalsCount ?? 0 }} Pending
                                </span>
                                <i class="fa-solid fa-chevron-right text-slate-400 group-hover:text-rose-600 text-xs transition"></i>
                            </div>
                        </a>

                        <!-- Permintaan Label QR -->
                        <a href="{{ route('qr-requests.index') }}" class="flex items-center justify-between p-3.5 bg-slate-50 hover:bg-teal-50/70 border border-slate-200/80 hover:border-teal-200 rounded-xl transition group">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-sm shadow-xs group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-qrcode"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-teal-900 transition">Permintaan Cetak Label QR / Barcode</h4>
                                    <p class="text-xs text-slate-500">Stiker thermal part nomor baru tiba & identifikasi rak</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-1 text-xs font-black rounded-full bg-teal-600 text-white">
                                    {{ \App\Models\QrRequest::where('status', 'pending')->count() }} Antre
                                </span>
                                <i class="fa-solid fa-chevron-right text-slate-400 group-hover:text-teal-600 text-xs transition"></i>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 text-right">
                    <span class="text-[11px] text-slate-400">Sinkronisasi otomatis dengan modul operasional</span>
                </div>
            </div>

            <!-- Akses Cepat Master Data & Konfigurasi -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base flex items-center">
                                <i class="fa-solid fa-boxes-packing text-teal-500 me-2"></i>
                                Master Data & Konfigurasi
                            </h3>
                            <p class="text-xs text-slate-500">Akses cepat pengaturan parameter dan struktur fisik gudang</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                        <!-- Satuan Ukur (UOM) -->
                        <a href="{{ route('admin.uoms.index') }}" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-xl transition group">
                            <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs mb-2">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-teal-700 transition">Satuan Ukur (UoM)</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ \App\Models\Uom::count() }} satuan terdaftar (Pcs, Set, Batang, dll)</p>
                        </a>

                        <!-- Gudang & Lokasi Rak -->
                        <a href="{{ route('admin.locations-master.index') }}" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-xl transition group">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs mb-2">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-blue-700 transition">Gudang & Lokasi Rak</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">{{ \App\Models\Location::count() }} titik rak di 6 zona gudang</p>
                        </a>

                        <!-- Pengaturan Sistem -->
                        <a href="{{ route('admin.settings.index') }}" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-xl transition group">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs mb-2">
                                <i class="fa-solid fa-gears"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-indigo-700 transition">Pengaturan Sistem</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Identitas aplikasi & batas keamanan</p>
                        </a>

                        <!-- Pengaturan Operasional -->
                        <a href="{{ route('admin.operational-settings.index') }}" class="p-4 bg-slate-50 hover:bg-amber-50/80 border border-slate-200/80 hover:border-amber-300 rounded-xl transition group">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs mb-2">
                                <i class="fa-solid fa-sliders"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-amber-700 transition">Pengaturan Operasional</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Kontrol alur scan QR Work Request & MH</p>
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Database: MySQL (InnoDB)</span>
                    <a href="{{ route('dashboard') }}" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center">
                        <span>Buka Dashboard Operasional</span>
                        <i class="fa-solid fa-arrow-right ms-1 text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
