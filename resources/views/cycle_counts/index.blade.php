<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                    Cycle Count (Stok Opname Berkala)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Penghitungan fisik stok berkala per zona gudang tanpa menghentikan operasional pabrik karoseri</p>
            </div>
            <a href="{{ route('cycle-counts.create') }}" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center">
                <i class="fa-solid fa-plus me-1.5"></i>
                Jadwalkan Cycle Count
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Status Filter Pills (Super Nav Pills) -->
        <div class="super-nav-pills flex-wrap">
            <a href="{{ route('cycle-counts.index') }}" class="super-nav-pill {{ !request('status') ? 'active' : '' }}">
                Semua Jadwal
            </a>
            <a href="{{ route('cycle-counts.index', ['status' => 'in_progress']) }}" class="super-nav-pill {{ request('status') == 'in_progress' ? 'active bg-amber-500 text-slate-950 font-bold' : '' }}">
                <i class="fa-solid fa-spinner me-1 text-amber-500"></i> Sedang Dihitung
            </a>
            <a href="{{ route('cycle-counts.index', ['status' => 'pending_review']) }}" class="super-nav-pill {{ request('status') == 'pending_review' ? 'active bg-blue-600 text-white' : '' }}">
                <i class="fa-solid fa-clipboard-user me-1 text-blue-500"></i> Review Supervisor
            </a>
            <a href="{{ route('cycle-counts.index', ['status' => 'reconciled']) }}" class="super-nav-pill {{ request('status') == 'reconciled' ? 'active bg-emerald-600 text-white' : '' }}">
                <i class="fa-solid fa-circle-check me-1 text-emerald-500"></i> Selesai Rekonsiliasi
            </a>
        </div>

        <!-- Super Table with Floating Separated Rows -->
        <div class="overflow-x-auto pb-4">
            <table class="super-table">
                <thead>
                    <tr>
                        <th class="py-3 px-4">No. Dokumen & Tanggal</th>
                        <th class="py-3 px-4">Zona Sasaran Opname</th>
                        <th class="py-3 px-4">Catatan Operasional</th>
                        <th class="py-3 px-4 text-center">Item Dihitung</th>
                        <th class="py-3 px-4">Status & Petugas</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cycleCounts as $cc)
                        <tr class="super-row">
                            <td class="py-4 px-4">
                                <span class="font-mono font-bold text-slate-900 block text-xs">{{ $cc->count_number }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $cc->count_date->format('d M Y') }}</span>
                            </td>

                            <td class="py-4 px-4">
                                <span class="font-extrabold text-xs px-2.5 py-1 rounded-lg bg-amber-100 text-amber-950 border border-amber-300 font-mono">
                                    ZONA {{ $cc->zone_target }}
                                </span>
                            </td>

                            <td class="py-4 px-4 max-w-xs">
                                <p class="text-slate-700 text-xs line-clamp-1">{{ $cc->notes ?: 'Opname berkala rak gudang HPK' }}</p>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="font-black text-slate-900 text-sm">{{ $cc->items->count() }} Part</span>
                            </td>

                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold border {{ $cc->status_badge['class'] }}">
                                    {{ $cc->status_badge['label'] }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Oleh: {{ $cc->conductedBy->name }}</span>
                            </td>

                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('cycle-counts.show', $cc) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-amber-100 text-slate-800 hover:text-amber-950 font-bold text-xs rounded-xl transition inline-flex items-center">
                                    Lembar Hitung <i class="fa-solid fa-arrow-right ms-1.5 text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 bg-white rounded-2xl shadow-sm">
                                <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-slate-300 block"></i>
                                Belum ada data cycle count / stok opname.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cycleCounts->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm">
                {{ $cycleCounts->links() }}
            </div>
        @endif

    </div>
</x-app-layout>
