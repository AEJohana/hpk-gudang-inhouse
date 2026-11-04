<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-6" x-data="{ modalOpen: false, modalTitle: '', modalJson: '', copySuccess: false }">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header !h-[250px] sm:!h-[280px]">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-clipboard-list me-1 text-[9px]"></i> AUDIT & LOG
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Keamanan & Jejak Aktivitas Terpadu
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div class="flex flex-col items-start gap-1 sm:gap-1.5">
                        <div>
                            <a href="{{ request()->routeIs('admin.*') ? route('admin.dashboard') : route('dashboard') }}" 
                               class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0" 
                               title="{{ request()->routeIs('admin.*') ? 'Kembali ke Pusat Kendali Administrator' : 'Kembali ke Beranda Utama' }}">
                                <i class="fa-solid fa-arrow-left text-[11px] text-amber-300"></i>
                                <span>Kembali</span>
                            </a>
                        </div>
                        <h1 class="font-black text-xl sm:text-3xl text-white tracking-wide leading-tight">
                            Riwayat Audit Aktivitas Sistem
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. 4 KARTU INDIKATOR KPI INTERAKTIF -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 -mt-10 sm:-mt-12 relative z-20">
            <!-- KPI 1: Aktivitas Hari Ini -->
            <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 block">Total Hari Ini</span>
                    <span class="text-2xl font-black text-slate-900 block mt-0.5">{{ number_format($kpis['total_today'] ?? 0) }}</span>
                    <span class="text-[11px] text-slate-400">Aktivitas tercatat</span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-lg shadow-xs">
                    <i class="fa-solid fa-bolt"></i>
                </div>
            </div>

            <!-- KPI 2: User Aktif Hari Ini -->
            <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-600 block">Pengguna Aktif</span>
                    <span class="text-2xl font-black text-slate-900 block mt-0.5">{{ number_format($kpis['users_active_today'] ?? 0) }}</span>
                    <span class="text-[11px] text-slate-400">User berinteraksi hari ini</span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 border border-teal-200 flex items-center justify-center text-lg shadow-xs">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <!-- KPI 3: Defect & Reject Kritis -->
            <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 block">Defect & Penolakan</span>
                    <span class="text-2xl font-black text-rose-600 block mt-0.5">{{ number_format($kpis['critical_today'] ?? 0) }}</span>
                    <span class="text-[11px] text-slate-400">Kejadian kritis hari ini</span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center text-lg shadow-xs">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>

            <!-- KPI 4: Modul Teraktif -->
            <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 block">Modul Teraktif</span>
                    <span class="text-base font-extrabold text-slate-900 block mt-1 truncate max-w-[130px] sm:max-w-none" title="{{ $kpis['top_module'] ?? '-' }}">
                        {{ $kpis['top_module'] ?? '-' }}
                    </span>
                    <span class="text-[11px] text-slate-400">Volume aksi tertinggi</span>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-lg shadow-xs">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
        </div>

        <!-- 3. AUDIT LOGS CARD WITH EXPANDED FILTERS -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
            
            <!-- Filter Toolbar -->
            <form method="GET" action="{{ request()->routeIs('admin.*') ? route('admin.audit-logs.index') : route('activities.index') }}" class="p-4 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-4">
                <!-- Row 1: Search Input -->
                <div class="relative w-full">
                    <label for="search" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Pencarian Kata Kunci
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               id="search" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Cari nama user, nomor WRI/TRX, kode part, event, atau aksi..." 
                               class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600 bg-white shadow-2xs transition">
                    </div>
                </div>

                <!-- Row 2: Dropdown Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <!-- Filter Rentang Waktu -->
                    <div>
                        <label for="period" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Rentang Waktu
                        </label>
                        <select name="period" id="period" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-300 bg-white focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
                            <option value="all" {{ request('period', 'all') === 'all' ? 'selected' : '' }}>Semua Riwayat</option>
                            <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Hari Ini (24 Jam)</option>
                            <option value="7_days" {{ request('period') === '7_days' ? 'selected' : '' }}>7 Hari Terakhir</option>
                            <option value="30_days" {{ request('period') === '30_days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                        </select>
                    </div>

                    <!-- Filter Modul -->
                    <div>
                        <label for="module" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Modul Aplikasi
                        </label>
                        <select name="module" id="module" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-300 bg-white focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
                            <option value="all">Semua Modul</option>
                            @foreach($modulesList as $modKey => $modLabel)
                                <option value="{{ $modKey }}" {{ request('module') === $modKey ? 'selected' : '' }}>{{ $modLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Pengguna -->
                    <div>
                        <label for="user_id" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Pengguna (User)
                        </label>
                        <select name="user_id" id="user_id" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-300 bg-white focus:border-cyan-600 focus:ring-1 focus:ring-cyan-600">
                            <option value="all">Semua Pengguna</option>
                            @foreach($usersList as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }} ({{ $u->department ?? 'Gudang' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2">
                        <button type="submit" 
                                class="flex-1 py-2 px-4 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center">
                            <i class="fa-solid fa-filter me-1.5"></i> Terapkan
                        </button>
                        @if(request()->hasAny(['search', 'period', 'module', 'user_id', 'event']))
                        <a href="{{ request()->routeIs('admin.*') ? route('admin.audit-logs.index') : route('activities.index') }}" 
                           class="py-2 px-3 bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 rounded-xl text-xs font-semibold transition" 
                           title="Reset Seluruh Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>

            <!-- Table Section -->
            <div class="overflow-x-auto rounded-xl border border-slate-200/80 shadow-xs">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-50 uppercase tracking-wider text-slate-600 font-extrabold border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5">Waktu & Tanggal</th>
                            <th class="px-5 py-3.5">Pengguna (Causer)</th>
                            <th class="px-5 py-3.5">Modul & Event</th>
                            <th class="px-5 py-3.5">Deskripsi Tindakan</th>
                            <th class="px-5 py-3.5 text-right">Detail Data (JSON)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($logs as $log)
                            @php
                                $eventSlug = strtolower($log->event ?? 'info');
                                $eventBadge = match($eventSlug) {
                                    'created', 'create' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    'updated', 'update' => 'bg-blue-100 text-blue-800 border-blue-300',
                                    'deleted', 'delete', 'rejected', 'defect' => 'bg-rose-100 text-rose-800 border-rose-300',
                                    'approved' => 'bg-teal-100 text-teal-800 border-teal-300',
                                    'login' => 'bg-amber-100 text-amber-800 border-amber-300',
                                    'logout' => 'bg-slate-100 text-slate-700 border-slate-300',
                                    'printed' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                    'supply', 'process' => 'bg-cyan-100 text-cyan-800 border-cyan-300',
                                    default => 'bg-purple-100 text-purple-800 border-purple-300',
                                };

                                $moduleSlug = strtolower($log->log_name ?? 'default');
                                $moduleBadge = match($moduleSlug) {
                                    'auth' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'work_request' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'material_handling' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                    'transaction' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'component' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'qr_label' => 'bg-teal-50 text-teal-700 border-teal-200',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Waktu -->
                                <td class="px-5 py-3.5 text-slate-500">
                                    <div class="font-bold text-slate-900">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">{{ $log->created_at->format('H:i:s') }} WIB</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                                </td>

                                <!-- Pengguna -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-800 text-amber-400 flex items-center justify-center font-bold text-xs shadow-2xs flex-shrink-0">
                                            {{ strtoupper(substr($log->causer->name ?? 'SYS', 0, 2)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 truncate max-w-[150px] sm:max-w-xs">{{ $log->causer->name ?? 'System Automated' }}</div>
                                            <div class="text-[10px] text-slate-400 truncate max-w-[150px]">{{ $log->causer->department ?? $log->causer->email ?? 'Otomatis' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Modul & Event -->
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $moduleBadge }}">
                                            {{ ucfirst(str_replace('_', ' ', $log->log_name ?? 'Modul')) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $eventBadge }}">
                                            {{ $log->event ?? 'Info' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Deskripsi -->
                                <td class="px-5 py-3.5 text-slate-700 max-w-md">
                                    <p class="whitespace-normal leading-relaxed text-xs">{{ $log->description }}</p>
                                    @if(isset($log->properties['ip']))
                                        <span class="text-[10px] text-slate-400 font-mono mt-0.5 inline-block">IP: {{ $log->properties['ip'] }}</span>
                                    @endif
                                </td>

                                <!-- Properti JSON Modal -->
                                <td class="px-5 py-3.5 text-right">
                                    @if($log->properties && $log->properties->count() > 0)
                                        <button type="button" 
                                                @click="modalOpen = true; modalTitle = 'Detail Log #{{ $log->id }} - {{ addslashes($log->description) }}'; modalJson = JSON.stringify({{ $log->properties->toJson() }}, null, 2); copySuccess = false;"
                                                class="inline-flex items-center px-3 py-1.5 rounded-xl bg-cyan-50 text-cyan-800 hover:bg-cyan-100 border border-cyan-200 text-xs font-bold transition shadow-2xs cursor-pointer">
                                            <i class="fa-solid fa-code me-1.5 text-cyan-600 text-[10px]"></i> Lihat Detail
                                        </button>
                                    @else
                                        <span class="text-slate-400 text-xs italic">Tanpa payload</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center text-slate-500">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl shadow-inner">
                                        <i class="fa-solid fa-clipboard-check"></i>
                                    </div>
                                    <p class="font-bold text-sm text-slate-800">Tidak ada riwayat log yang ditemukan</p>
                                    <p class="text-xs text-slate-400 mt-1">Belum ada aktivitas yang sesuai dengan kriteria filter yang Anda pilih.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(method_exists($logs, 'hasPages') && $logs->hasPages())
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                {{ $logs->links() }}
            </div>
            @endif

        </div>

        <!-- 4. MODAL JSON VIEWER WITH COPY CLIPBOARD BUTTON -->
        <div x-show="modalOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
            
            <div @click.away="modalOpen = false" 
                 class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 transition-all">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-[#0a2342] text-white flex items-center justify-between">
                    <div class="flex items-center space-x-2.5 min-w-0">
                        <i class="fa-solid fa-file-code text-amber-400 text-base"></i>
                        <h3 class="text-xs font-bold text-white truncate max-w-md" x-text="modalTitle"></h3>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body: Formatted JSON -->
                <div class="p-6 bg-slate-900 overflow-x-auto max-h-[450px]">
                    <pre class="font-mono text-xs text-amber-300 leading-relaxed" x-text="modalJson"></pre>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-slate-100 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" 
                            @click="navigator.clipboard.writeText(modalJson); copySuccess = true; setTimeout(() => copySuccess = false, 2000);" 
                            class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-2xs transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-copy text-slate-500"></i>
                        <span x-text="copySuccess ? 'Tersalin!' : 'Salin JSON'"></span>
                    </button>
                    <button type="button" 
                            @click="modalOpen = false" 
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
