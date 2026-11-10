<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-2.5 sm:gap-4 w-full">
            <div class="min-w-0">
                <h2 class="font-extrabold text-base sm:text-2xl text-slate-900 tracking-tight truncate">
                    <span class="sr-only">Safety Stock Buffer Alert</span>
                    Safety Stock & Pemicu Order WRI
                </h2>
            </div>

            <div class="flex items-center space-x-2 shrink-0 ml-auto">
                <a href="{{ route('work-requests.index') }}" class="px-3 sm:px-3.5 py-1.5 sm:py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center shadow-2xs">
                    <i class="fa-solid fa-arrow-left me-1.5"></i>
                    <span class="hidden sm:inline">Kembali ke Antrian WRI</span>
                    <span class="sm:hidden">Antrian WRI</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- 0. INTEGRATED WRI SUB-NAVIGATION TABS (Antrean, Safety Stock, Cetak WR) -->
        @include('work_requests.partials.sub_nav')

        <!-- 1. ALERT BANNER -->
        @if($totalCritical > 0)
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg font-bold shrink-0">
                    <i class="fa-solid fa-bell animate-bounce"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm text-rose-900">Peringatan: Stok Komponen Di Bawah Batas Minimum</h4>
                    <p class="text-xs text-rose-700 mt-0.5">Komponen berikut berisiko habis. Segera terbitkan Work Request (WRI) ke mesin pemotongan / bending terkait.</p>
                </div>
            </div>
            <span class="hidden sm:inline-block px-3 py-1 bg-rose-600 text-white text-xs font-black rounded-lg shrink-0">
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

        <!-- 2. CRITICAL COMPONENTS CONTAINER (DUAL VIEW: DESKTOP TABLE & MOBILE CARDS) -->
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

            <!-- DESKTOP / TABLET VIEW (TABLE WITH MIN-WIDTH & NO-WRAP BADGES) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 min-w-[720px]">
                    <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4 min-w-[200px]">Komponen</th>
                            <th class="py-3 px-3 min-w-[90px]">Kategori</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Stok Fisik</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Safety Stock</th>
                            <th class="py-3 px-3 text-center whitespace-nowrap">Defisit</th>
                            <th class="py-3 px-3 whitespace-nowrap">Lokasi Default</th>
                            <th class="py-3 px-4 text-end whitespace-nowrap">Aksi Pemicu</th>
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
                                    <img src="{{ route('components.photo-stream', ['component' => $comp->id]) }}" 
                                         alt="{{ $comp->name }}"
                                         loading="lazy"
                                         class="w-10 h-10 rounded-lg object-cover bg-slate-100 border border-slate-200 flex-shrink-0">
                                    <div>
                                        <span class="font-extrabold text-slate-900 block leading-tight">{{ $comp->name }}</span>
                                        <span class="inline-block font-mono text-[10px] text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded font-bold mt-0.5">
                                            {{ $comp->part_number }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-slate-700 font-medium">
                                {{ $comp->category->name ?? 'Fabrikasi' }}
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="text-sm font-black text-rose-600">
                                    {{ number_format($comp->total_stock, 0) }}
                                </span>
                                <span class="text-[10px] text-slate-400 block uppercase">{{ $comp->uom }}</span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-700 whitespace-nowrap">
                                {{ number_format($comp->minimum_stock, 0) }} <span class="text-[10px] uppercase font-normal text-slate-400">{{ $comp->uom }}</span>
                            </td>
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black font-mono bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs whitespace-nowrap">
                                    <span>-{{ number_format($deficit, 0) }}</span>
                                    <span class="text-[10px] font-bold text-rose-600 uppercase">{{ $comp->uom }}</span>
                                </span>
                            </td>
                            <td class="py-3 px-3 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                {{ $comp->defaultLocation ? $comp->defaultLocation->code : '-' }}
                            </td>
                            <td class="py-3 px-4 text-end whitespace-nowrap">
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

            <!-- MOBILE VIEW (< md): TOUCH-FRIENDLY COMPONENT CARDS (ANTI-CUTOFF & SUPER FLEXIBLE) -->
            <div class="block md:hidden divide-y divide-slate-100">
                @forelse($criticalComponents as $comp)
                @php
                    $deficit = max(0, $comp->minimum_stock - $comp->total_stock);
                @endphp
                <div class="p-4 space-y-3 hover:bg-rose-50/20 transition">
                    <!-- Top Row: Thumbnail + Component Name + Part Number -->
                    <div class="flex items-start space-x-3">
                        <img src="{{ route('components.photo-stream', ['component' => $comp->id]) }}" 
                             alt="{{ $comp->name }}"
                             loading="lazy"
                             class="w-12 h-12 rounded-xl object-cover bg-slate-100 border border-slate-200 shrink-0">
                        <div class="min-w-0 flex-1">
                            <h5 class="font-extrabold text-xs text-slate-900 leading-snug truncate">{{ $comp->name }}</h5>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="font-mono text-[10px] text-amber-900 bg-amber-100/80 px-1.5 py-0.5 rounded font-bold">
                                    {{ $comp->part_number }}
                                </span>
                                <span class="text-[10px] text-slate-500 font-medium">
                                    {{ $comp->category->name ?? 'Fabrikasi' }}
                                </span>
                                <span class="text-[10px] text-slate-400 font-mono">
                                    &bull; Rak: {{ $comp->defaultLocation ? $comp->defaultLocation->code : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Metric 3-Column Grid: Stok Fisik, Safety Stock, Defisit (Single-Line, No Wrapping) -->
                    <div class="grid grid-cols-3 gap-2 p-2.5 bg-slate-50 rounded-xl border border-slate-100 text-center">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-medium">Stok Fisik</span>
                            <span class="text-xs font-black text-rose-600 font-mono">{{ number_format($comp->total_stock, 0) }}</span>
                            <span class="text-[9px] text-slate-400 block uppercase">{{ $comp->uom }}</span>
                        </div>
                        <div class="border-x border-slate-200/80">
                            <span class="text-[10px] text-slate-400 block font-medium">Safety Stock</span>
                            <span class="text-xs font-bold text-slate-700 font-mono">{{ number_format($comp->minimum_stock, 0) }}</span>
                            <span class="text-[9px] text-slate-400 block uppercase">{{ $comp->uom }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-rose-600 block font-bold">Defisit</span>
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-md text-xs font-black font-mono bg-rose-100 text-rose-800 border border-rose-300 whitespace-nowrap">
                                -{{ number_format($deficit, 0) }} <span class="text-[9px] uppercase ml-0.5">{{ $comp->uom }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Action Button (Full Width on Mobile) -->
                    <div>
                        @can('work-request.create')
                        <a href="{{ route('work-requests.create', ['component_id' => $comp->id, 'quantity' => max(10, $deficit * 2)]) }}" 
                           class="w-full py-2.5 px-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black transition shadow-xs flex items-center justify-center space-x-1.5 active:scale-[0.98]">
                            <i class="fa-solid fa-plus text-[10px]"></i>
                            <span>Pesan WRI Sekarang (Picu Order)</span>
                        </a>
                        @else
                        <div class="text-center py-1 text-[11px] text-slate-400 italic">Hanya Pemohon yang dapat memicu WRI</div>
                        @endcan
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-slate-400 italic text-xs">
                    Tidak ada komponen yang defisit di bawah batas safety stock.
                </div>
                @endforelse
            </div>

            @if($criticalComponents->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $criticalComponents->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
