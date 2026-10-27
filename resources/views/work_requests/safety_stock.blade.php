<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 text-[11px] font-bold uppercase rounded-md bg-rose-100 text-rose-800 border border-rose-300">
                        <i class="fa-solid fa-shield-halved me-1 text-rose-600"></i> Safety Stock Buffer Alert
                    </span>
                    <span class="text-xs text-slate-500 font-medium">Monitoring Ambang Batas Stok Kritis</span>
                </div>
                <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight mt-1">
                    Safety Stock & Pemicu Order WRI
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Daftar komponen karoseri yang stok fisiknya berada di bawah batas aman minimum untuk memicu pembuatan tiket WRI sebelum terjadi kelangkaan di lini produksi
                </p>
            </div>

            <div class="flex items-center space-x-2 self-start sm:self-auto">
                <a href="{{ route('work-requests.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center">
                    <i class="fa-solid fa-arrow-left me-1.5"></i>
                    Kembali ke Antrian WRI
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-3">
            <div class="flex items-center space-x-2">
                <a href="{{ route('work-requests.index') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-list-check text-xs"></i>
                    <span>Antrian & Tiket WRI</span>
                </a>
                <a href="{{ route('work-requests.safety-stock') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 bg-[#0a2342] text-amber-400 shadow-md whitespace-nowrap">
                    <i class="fa-solid fa-shield-halved text-xs text-amber-400"></i>
                    <span>Safety Stock Alert</span>
                </a>
                @can('work-request.print')
                <a href="{{ route('work-requests.print-queue') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition flex items-center space-x-1.5 text-slate-600 hover:bg-slate-100 whitespace-nowrap">
                    <i class="fa-solid fa-print text-xs text-indigo-500"></i>
                    <span>Cetak Dokumen WR</span>
                </a>
                @endcan
            </div>

            <div class="flex items-center space-x-2 text-xs">
                <span class="text-slate-400 font-semibold">Status Gudang:</span>
                <span class="px-2.5 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 font-extrabold flex items-center space-x-1">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                    <span>{{ $totalCritical }} Komponen Butuh Restock</span>
                </span>
            </div>
        </div>

        <!-- 1. ALERT BANNER -->
        @if($totalCritical > 0)
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-bell animate-bounce"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-rose-900">Peringatan: Stok Komponen Di Bawah Batas Minimum</h4>
                    <p class="text-xs text-rose-700 mt-0.5">Komponen berikut berisiko habis. Segera terbitkan Work Request (WRI) ke mesin pemotongan / bending terkait.</p>
                </div>
            </div>
            <span class="hidden sm:inline-block px-3 py-1 bg-rose-600 text-white text-xs font-black rounded-lg">
                {{ $totalCritical }} Kritis
            </span>
        </div>
        @else
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl mx-auto mb-2">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h4 class="font-extrabold text-base text-emerald-900">Semua Saldo Stok Aman</h4>
            <p class="text-xs text-emerald-700 mt-0.5">Tidak ada komponen fabrikasi yang berada di bawah ambang batas safety stock saat ini.</p>
        </div>
        @endif

        <!-- 2. TABLE CRITICAL COMPONENTS -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-boxes-stacked text-slate-700"></i>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-800">Daftar Komponen Defisit Stok</h4>
                </div>

                <!-- Search -->
                <form method="GET" action="{{ route('work-requests.safety-stock') }}" class="relative w-full sm:w-72">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           name="search" 
                           value="{{ request()->search }}" 
                           placeholder="Cari part number atau nama..." 
                           class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">Komponen</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3 text-center">Stok Fisik</th>
                            <th class="py-3 px-3 text-center">Safety Stock</th>
                            <th class="py-3 px-3 text-center">Defisit</th>
                            <th class="py-3 px-3">Lokasi Default</th>
                            <th class="py-3 px-4 text-end">Aksi Pemicu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($criticalComponents as $comp)
                        @php
                            $deficit = max(0, $comp->minimum_stock - $comp->total_stock);
                        @endphp
                        <tr class="hover:bg-rose-50/30 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ $comp->image_url }}" class="w-10 h-10 rounded-lg object-cover bg-slate-100 border border-slate-200 flex-shrink-0">
                                    <div>
                                        <span class="font-extrabold text-slate-900 block">{{ $comp->name }}</span>
                                        <span class="font-mono text-[10px] text-amber-900 bg-amber-100/80 px-1.5 py-0.2 rounded font-bold">
                                            {{ $comp->part_number }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-slate-700 font-medium">
                                {{ $comp->category->name ?? 'Fabrikasi' }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="text-sm font-black text-rose-600">
                                    {{ number_format($comp->total_stock, 0) }}
                                </span>
                                <span class="text-[10px] text-slate-400 block">{{ $comp->uom }}</span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-700">
                                {{ number_format($comp->minimum_stock, 0) }} {{ $comp->uom }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">
                                    -{{ number_format($deficit, 0) }} {{ $comp->uom }}
                                </span>
                            </td>
                            <td class="py-3 px-3 font-mono text-[11px] text-slate-600">
                                {{ $comp->defaultLocation ? $comp->defaultLocation->code : '-' }}
                            </td>
                            <td class="py-3 px-4 text-end">
                                @can('work-request.create')
                                <a href="{{ route('work-requests.create', ['component_id' => $comp->id, 'quantity' => max(10, $deficit * 2)]) }}" 
                                   class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black transition shadow-xs">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Pesan WRI</span>
                                </a>
                                @else
                                <span class="text-[10px] text-slate-400 italic">Hanya Pemohon</span>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                Tidak ada komponen yang defisit di bawah batas safety stock.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($criticalComponents->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $criticalComponents->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
