<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
            <div class="flex flex-col items-start gap-1 sm:gap-1.5">
                <div>
                    <a href="{{ route('dashboard') }}" 
                       class="inline-flex items-center space-x-1.5 px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer shrink-0" 
                       title="Kembali ke Beranda Utama">
                        <i class="fa-solid fa-arrow-left text-[11px] text-slate-500"></i>
                        <span>Kembali</span>
                    </a>
                </div>
                <h2 class="font-extrabold text-xl sm:text-2xl text-slate-900 tracking-tight leading-tight">
                    Cycle Count (Stok Opname Berkala)
                </h2>
            </div>
            <a href="{{ route('cycle-counts.create') }}" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow-md transition flex items-center justify-center w-full sm:w-auto">
                <i class="fa-solid fa-plus me-1.5"></i>
                Jadwalkan Cycle Count
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Status Filter Pills (Super Nav Pills) -->
        <div class="super-nav-pills flex-nowrap overflow-x-auto no-scrollbar sm:flex-wrap pb-1">
            <a href="{{ route('cycle-counts.index') }}" class="super-nav-pill {{ !request('status') ? 'active' : '' }} whitespace-nowrap flex-shrink-0">
                Semua Jadwal
            </a>
            <a href="{{ route('cycle-counts.index', ['status' => 'in_progress']) }}" class="super-nav-pill {{ request('status') == 'in_progress' ? 'active bg-amber-500 text-slate-950 font-bold' : '' }} whitespace-nowrap flex-shrink-0">
                <i class="fa-solid fa-spinner me-1 text-amber-500"></i> Sedang Dihitung
            </a>
            <a href="{{ route('cycle-counts.index', ['status' => 'pending_review']) }}" class="super-nav-pill {{ request('status') == 'pending_review' ? 'active bg-blue-600 text-white' : '' }} whitespace-nowrap flex-shrink-0">
                <i class="fa-solid fa-clipboard-user me-1 text-blue-500"></i> Review Supervisor
            </a>
            <a href="{{ route('cycle-counts.index', ['status' => 'reconciled']) }}" class="super-nav-pill {{ request('status') == 'reconciled' ? 'active bg-emerald-600 text-white' : '' }} whitespace-nowrap flex-shrink-0">
                <i class="fa-solid fa-circle-check me-1 text-emerald-500"></i> Selesai Rekonsiliasi
            </a>
        </div>

        <!-- Mobile Card View (< md) -->
        <div class="block md:hidden space-y-3">
            @forelse ($cycleCounts as $cc)
                <div class="mobile-data-card">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                        <div>
                            <span class="font-mono font-bold text-slate-900 text-xs block">{{ $cc->count_number }}</span>
                            <span class="text-[10px] text-slate-400 font-medium">{{ $cc->count_date->format('d M Y') }}</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border {{ $cc->status_badge['class'] }}">
                            {{ $cc->status_badge['label'] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs py-1">
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-semibold">Zona Sasaran</span>
                            <span class="font-extrabold text-xs px-2 py-0.5 rounded-lg bg-amber-100 text-amber-950 border border-amber-300 font-mono inline-block mt-0.5">
                                ZONA {{ $cc->zone_target }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 block uppercase font-semibold">Item Dihitung</span>
                            <span class="font-black text-slate-900 text-sm block mt-0.5">{{ $cc->items->count() }} Part</span>
                        </div>
                    </div>

                    <div class="text-xs text-slate-600 bg-slate-50 p-2 rounded-xl border border-slate-100">
                        <span class="text-[10px] text-slate-400 block">Catatan Operasional:</span>
                        <p class="text-xs text-slate-700 line-clamp-2 mt-0.5">{{ $cc->notes ?: 'Opname berkala rak gudang HPK' }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                        <span class="text-[10px] text-slate-400">Oleh: <strong class="text-slate-600">{{ $cc->conductedBy->name }}</strong></span>
                        <a href="{{ route('cycle-counts.show', $cc) }}" class="px-3.5 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-950 font-bold text-xs rounded-xl transition inline-flex items-center">
                            Lembar Hitung <i class="fa-solid fa-arrow-right ms-1.5 text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400 bg-white rounded-2xl border border-slate-200 p-6">
                    <i class="fa-solid fa-clipboard-list text-3xl mb-2 text-slate-300 block"></i>
                    Belum ada data cycle count / stok opname.
                </div>
            @endforelse
        </div>

        <!-- Super Table with Floating Separated Rows (Desktop >= md) -->
        <div class="hidden md:block overflow-x-auto pb-4">
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
