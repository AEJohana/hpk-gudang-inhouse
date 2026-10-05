<x-admin-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-12 space-y-8" x-data="{ modalOpen: false, modalTitle: '', modalJson: '' }">

        <!-- 1. HERO SECTION WITH CURVED WAVE HEADER -->
        <div class="relative -mt-4 -mx-4 sm:-mx-6 lg:-mx-8 mb-6 overflow-hidden">
            <div class="wave-header" style="height: 240px;">
                <div class="wave-shape-2"></div>
            </div>

            <div class="relative z-10 pt-6 pb-12 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2.5 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-slate-950 uppercase tracking-widest shadow-xs">
                        <i class="fa-solid fa-clipboard-list me-1 text-[9px]"></i> AUDIT & LOG
                    </span>
                    <span class="text-xs font-semibold text-teal-300 uppercase tracking-wider">
                        Keamanan & Jejak Aktivitas
                    </span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="font-black text-2xl sm:text-3xl text-white tracking-wide">
                            Riwayat Audit Aktivitas Sistem
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Log pencatatan otomatis transaksi, perubahan data master komponen, approval ECR, dan tindakan administratif.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. AUDIT LOGS CARD -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            
            <!-- Filter Toolbar -->
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-6 p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                <div class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1">
                        <label for="search" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Cari Log Aktivitas
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" 
                                   id="search" 
                                   name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Ketik user, jenis aksi, atau kata kunci..." 
                                   class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 bg-white shadow-xs transition">
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="submit" 
                                class="py-2 px-4 bg-[#0a2342] hover:bg-slate-800 text-amber-400 font-bold text-xs rounded-xl shadow-xs transition flex items-center justify-center">
                            <i class="fa-solid fa-filter me-1.5"></i> Filter Log
                        </button>
                        @if(request('search'))
                        <a href="{{ route('admin.audit-logs.index') }}" 
                           class="py-2 px-3 bg-white hover:bg-slate-100 text-slate-600 border border-slate-300 rounded-xl text-xs font-semibold transition" 
                           title="Reset Pencarian">
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
                            <th class="px-5 py-3.5">Jenis Event</th>
                            <th class="px-5 py-3.5">Deskripsi Tindakan</th>
                            <th class="px-5 py-3.5 text-right">Detail Data (JSON)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($logs as $log)
                            @php
                                $eventSlug = strtolower($log->event ?? $log->log_name ?? 'info');
                                $eventBadge = match($eventSlug) {
                                    'created', 'create' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'updated', 'update' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'deleted', 'delete' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    'login' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    default => 'bg-purple-100 text-purple-800 border-purple-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <!-- Waktu -->
                                <td class="px-5 py-3.5 text-slate-500">
                                    <div class="font-bold text-slate-900">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ $log->created_at->format('H:i:s') }} WIB</div>
                                </td>

                                <!-- Pengguna -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center space-x-2.5">
                                        <div class="w-7 h-7 rounded-full bg-slate-800 text-amber-400 flex items-center justify-center font-bold text-[10px]">
                                            {{ strtoupper(substr($log->causer->name ?? 'SYS', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $log->causer->name ?? 'System Automated' }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $log->causer->email ?? 'internal-event' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Event -->
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $eventBadge }}">
                                        {{ $log->event ?? $log->log_name }}
                                    </span>
                                </td>

                                <!-- Deskripsi -->
                                <td class="px-5 py-3.5 text-slate-700 max-w-sm truncate">
                                    {{ $log->description }}
                                </td>

                                <!-- Properti JSON Modal -->
                                <td class="px-5 py-3.5 text-right">
                                    @if($log->properties && $log->properties->count() > 0)
                                        <button type="button" 
                                                @click="modalOpen = true; modalTitle = 'Log #{{ $log->id }} - {{ $log->description }}'; modalJson = JSON.stringify({{ $log->properties->toJson() }}, null, 2);"
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-semibold transition">
                                            <i class="fa-solid fa-code me-1 text-[10px]"></i> Lihat JSON
                                        </button>
                                    @else
                                        <span class="text-slate-400 text-xs italic">Tanpa payload</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                                        <i class="fa-solid fa-clipboard-check"></i>
                                    </div>
                                    <p class="font-bold text-sm text-slate-700">Tidak ada riwayat log yang ditemukan</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if(method_exists($logs, 'links'))
            <div class="mt-5">
                {{ $logs->links() }}
            </div>
            @endif

        </div>

        <!-- MODAL JSON VIEWER -->
        <div x-show="modalOpen" 
             style="display: none;" 
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            
            <div @click.away="modalOpen = false" 
                 class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 transition-all">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-[#0a2342] text-white flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-file-code text-amber-400"></i>
                        <h3 class="text-sm font-bold text-white truncate max-w-md" x-text="modalTitle"></h3>
                    </div>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body: Formatted JSON -->
                <div class="p-6 bg-slate-900 overflow-x-auto max-h-[450px]">
                    <pre class="font-mono text-xs text-amber-300" x-text="modalJson"></pre>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-slate-100 border-t border-slate-200 flex justify-end">
                    <button type="button" 
                            @click="modalOpen = false" 
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
