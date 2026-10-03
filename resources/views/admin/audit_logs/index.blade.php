<x-admin-layout>
    <div class="wave-header pb-12 pt-8 relative overflow-hidden" style="min-height: 180px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="bg-hpk-orange/20 text-amber-300 border border-amber-300/50 text-[10px] font-bold px-2 py-0.5 rounded-full tracking-widest uppercase">
                            Admin
                        </span>
                        <span class="text-teal-300 text-xs font-bold tracking-widest uppercase">Audit Log</span>
                    </div>
                    <h1 class="text-3xl font-black text-white tracking-wide mb-1">
                        Riwayat Aktivitas Sistem
                    </h1>
                </div>
            </div>
        </div>
        <div class="wave-shape-2">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0,60 C320,120 420,0 720,60 C1020,120 1120,0 1440,60 L1440,120 L0,120 Z" fill="#f0f4f8"></path>
            </svg>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20 pb-12">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            
            <!-- Filter Section -->
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-6">
                <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="Cari (User / Aksi / Deskripsi)" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Ketik kata kunci pencarian..." />
                    </div>
                    <div>
                        <x-primary-button type="submit" class="w-full justify-center">Cari Log</x-primary-button>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="uppercase tracking-wider text-slate-500 font-bold bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Waktu</th>
                            <th class="px-6 py-4">Pengguna</th>
                            <th class="px-6 py-4">Aksi / Event</th>
                            <th class="px-6 py-4">Deskripsi</th>
                            <th class="px-6 py-4">Properti Tambahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">
                                {{ $log->causer->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-indigo-100 text-indigo-700 font-bold px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wider">
                                    {{ $log->event ?? $log->log_name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4">
                                <button class="text-xs text-blue-600 hover:underline focus:outline-none" onclick="alert('{{ json_encode($log->properties) }}')">
                                    Lihat Detail JSON
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                Tidak ada log yang ditemukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
            
        </div>
    </div>
</x-admin-layout>
