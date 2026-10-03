<x-admin-layout>
    <!-- Wave Header -->
    <div class="wave-header pb-16 pt-8 relative overflow-hidden" style="min-height: 200px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center space-x-3 mb-2">
                <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                    Admin
                </span>
                <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Admin Panel Aktif</span>
            </div>
            
            <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                        Dashboard Administrator
                    </h1>
                    <p class="text-slate-300 text-sm">
                        Kelola pengguna, otorisasi, dan data referensi (Master Data) WMS.
                    </p>
                </div>
                <div class="mt-4 md:mt-0 text-white/80 font-medium text-sm flex items-center space-x-2">
                    <i class="fa-regular fa-calendar-days text-teal-400"></i>
                    <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
            </div>
        </div>
        <!-- Decorative SVG wave -->
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20 pb-12 space-y-8">
        
        <!-- Stats Row -->
        <div class="dashboard-grid">
            <!-- Users Card -->
            <a href="{{ route('admin.users.index') }}" class="dash-card">
                <div class="flex justify-between items-start">
                    <div class="icon-box !bg-amber-100 !text-amber-600 !shadow-amber-500/20">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="text-right">
                        <h3 class="text-3xl font-black text-slate-800">{{ $usersCount ?? \App\Models\User::count() }}</h3>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">User Aktif</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs font-semibold text-amber-600">
                    <span>Kelola Pengguna & Akses</span>
                    <i class="fa-solid fa-arrow-right ml-1 arrow-action"></i>
                </div>
                <i class="fa-solid fa-users card-icon-bg"></i>
            </a>

            <!-- Roles Card -->
            <a href="{{ route('admin.roles.index') }}" class="dash-card">
                <div class="flex justify-between items-start">
                    <div class="icon-box !bg-blue-100 !text-blue-600 !shadow-blue-500/20">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="text-right">
                        <h3 class="text-3xl font-black text-slate-800">{{ $rolesCount ?? \Spatie\Permission\Models\Role::count() }}</h3>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">Role Sistem</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs font-semibold text-blue-600">
                    <span>Atur Matriks Permission</span>
                    <i class="fa-solid fa-arrow-right ml-1 arrow-action"></i>
                </div>
                <i class="fa-solid fa-user-shield card-icon-bg"></i>
            </a>

            <!-- Categories Card -->
            <a href="{{ route('admin.component-categories.index') }}" class="dash-card">
                <div class="flex justify-between items-start">
                    <div class="icon-box !bg-teal-100 !text-teal-600 !shadow-teal-500/20">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                    <div class="text-right">
                        <h3 class="text-3xl font-black text-slate-800">{{ $categoriesCount ?? \App\Models\ComponentCategory::count() }}</h3>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">Kategori Part</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs font-semibold text-teal-600">
                    <span>Lihat Master Kategori</span>
                    <i class="fa-solid fa-arrow-right ml-1 arrow-action"></i>
                </div>
                <i class="fa-solid fa-tags card-icon-bg"></i>
            </a>

            <!-- Logs Card -->
            <a href="{{ route('admin.audit-logs.index') }}" class="dash-card">
                <div class="flex justify-between items-start">
                    <div class="icon-box !bg-rose-100 !text-rose-600 !shadow-rose-500/20">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="text-right">
                        <h3 class="text-3xl font-black text-slate-800">{{ $logsCount ?? \Spatie\Activitylog\Models\Activity::count() }}</h3>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mt-1">Audit Log</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs font-semibold text-rose-600">
                    <span>Pantau Aktivitas</span>
                    <i class="fa-solid fa-arrow-right ml-1 arrow-action"></i>
                </div>
                <i class="fa-solid fa-clipboard-list card-icon-bg"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
            <!-- Peringatan Sistem -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="font-bold text-slate-900 text-lg">Peringatan Sistem</h2>
                        <p class="text-xs text-slate-500">Notifikasi admin & isu data</p>
                    </div>
                    <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded-full">Perlu Perhatian</span>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-start space-x-3 p-3 bg-rose-50 rounded-xl border border-rose-100">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Terdapat 3 Akun Terkunci</p>
                            <p class="text-xs text-slate-600">Terjadi kesalahan login berturut-turut.</p>
                        </div>
                    </li>
                    <li class="flex items-start space-x-3 p-3 bg-amber-50 rounded-xl border border-amber-100">
                        <i class="fa-solid fa-clock text-amber-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">2 User Tidak Aktif > 30 Hari</p>
                            <p class="text-xs text-slate-600">Pertimbangkan untuk menonaktifkan akun.</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Antrian Tugas -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="font-bold text-slate-900 text-lg">Antrian Persetujuan</h2>
                        <p class="text-xs text-slate-500">Shortcut antrian lintas modul</p>
                    </div>
                </div>
                <ul class="space-y-3">
                    <li class="flex items-center justify-between p-3 bg-purple-50 hover:bg-purple-100 rounded-xl border border-purple-100 transition cursor-pointer" onclick="window.location.href='{{ route('ecrs.index') }}'">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-purple-200 flex items-center justify-center text-purple-700">
                                <i class="fa-solid fa-file-pen text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">ECR Menunggu Review</p>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">Modul Engineering</p>
                            </div>
                        </div>
                        <span class="bg-purple-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm">{{ $pendingEcrsCount }}</span>
                    </li>
                    
                    <li class="flex items-center justify-between p-3 bg-rose-50 hover:bg-rose-100 rounded-xl border border-rose-100 transition cursor-pointer" onclick="window.location.href='{{ route('disposals.index') }}'">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-full bg-rose-200 flex items-center justify-center text-rose-700">
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Disposal Pending</p>
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">Modul Scrap/Afkir</p>
                            </div>
                        </div>
                        <span class="bg-rose-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm">{{ $pendingDisposalsCount }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</x-admin-layout>
