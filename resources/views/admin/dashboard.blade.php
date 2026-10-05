<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 270px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <!-- Top Badge Row -->
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-shield-halved me-1 text-[9px]"></i> SUPER ADMIN
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        PT Hydraxle Perkasa Karoseri
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Pusat Kendali Administrator
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Manajemen terpusat akun pengguna, hak akses per divisi, referensi master data, konfigurasi sistem, dan pemantauan audit log WMS.
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center space-x-2.5">
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
                    <h3 class="font-bold text-slate-900 text-lg">Ringkasan Sistem & Master Data</h3>
                    <p class="text-xs text-slate-500">Statistik real-time entitas master dan keamanan aplikasi</p>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- 1. Pengguna Aktif -->
                <a href="{{ route('admin.users.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-users card-icon-bg"></i>
                    <div class="icon-box text-amber-600 bg-amber-50">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-amber-600 transition">Manajemen User</h4>
                            <span class="text-2xl font-black text-slate-900">{{ $usersCount ?? \App\Models\User::count() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Akun operasional gudang, divisi fabrikasi & approval</p>
                    </div>
                </a>

                <!-- 2. Role & Akses -->
                <a href="{{ route('admin.roles.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-user-shield card-icon-bg"></i>
                    <div class="icon-box text-blue-600 bg-blue-50">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-blue-600 transition">Role & Permission</h4>
                            <span class="text-2xl font-black text-slate-900">{{ $rolesCount ?? \Spatie\Permission\Models\Role::count() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Matriks kewenangan 5 divisi & 54 izin sistem</p>
                    </div>
                </a>

                <!-- 3. Kategori Komponen -->
                <a href="{{ route('admin.component-categories.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-tags card-icon-bg"></i>
                    <div class="icon-box text-teal-600 bg-teal-50">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-teal-600 transition">Kategori Komponen</h4>
                            <span class="text-2xl font-black text-slate-900">{{ $categoriesCount ?? \App\Models\ComponentCategory::count() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Kelompok hidrolik, raw material baja, fastener & cat</p>
                    </div>
                </a>

                <!-- 4. Audit Log -->
                <a href="{{ route('admin.audit-logs.index') }}" class="dash-card group">
                    <i class="fa-solid fa-arrow-right arrow-action"></i>
                    <i class="fa-solid fa-clipboard-list card-icon-bg"></i>
                    <div class="icon-box text-rose-600 bg-rose-50">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-extrabold text-slate-900 text-base group-hover:text-rose-600 transition">Audit Log Aktivitas</h4>
                            <span class="text-2xl font-black text-slate-900">{{ $logsCount ?? \Spatie\Activitylog\Models\Activity::count() }}</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">Pencatatan riwayat perubahan data & keamanan sistem</p>
                    </div>
                </a>
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
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
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs mb-2">
                                <i class="fa-solid fa-gears"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-amber-700 transition">Pengaturan Sistem</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Nama aplikasi, mata uang & batas keamanan login</p>
                        </a>

                        <!-- Peta Layout Gudang -->
                        <a href="{{ route('warehouse-map.index') }}" class="p-4 bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 rounded-xl transition group">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs mb-2">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-700 transition">Peta Denah Gudang</h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Visualisasi interaktif 1 gedung HPK Karoseri</p>
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
