<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center space-x-3">
                <a href="{{ route('cycle-counts.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded">{{ $cycleCount->count_number }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $cycleCount->status_badge['class'] }}">
                            {{ $cycleCount->status_badge['label'] }}
                        </span>
                    </div>
                    <h2 class="font-bold text-xl text-slate-900 leading-tight mt-1">
                        Lembar Penghitungan Fisik - ZONA {{ $cycleCount->zone_target }}
                    </h2>
                </div>
            </div>

            <!-- Reconciliation Action for Supervisor -->
            @if ($cycleCount->status === 'pending_review')
                <form method="POST" action="{{ route('cycle-counts.reconcile', $cycleCount) }}" onsubmit="return confirm('Apakah Anda menyetujui hasil stok opname ini? Seluruh saldo stok fisik di sistem akan disesuaikan secara otomatis!')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center">
                        <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Setujui Rekonsiliasi & Update Saldo Stok
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Overview Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Sasaran Opname:</span>
                <h3 class="font-bold text-base text-slate-900">ZONA {{ $cycleCount->zone_target }} - Gudang HPK Karoseri</h3>
                <p class="text-xs text-slate-500 mt-0.5">Petugas Hitung: <strong>{{ $cycleCount->conductedBy->name }}</strong> &bull; Tgl: {{ $cycleCount->count_date->format('d M Y') }}</p>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                <div class="px-3 py-1.5 bg-slate-50 rounded-xl border border-slate-200 text-center">
                    <span class="text-slate-400 text-[10px] block font-bold">TOTAL ITEM</span>
                    <span class="font-bold text-slate-900 text-sm">{{ $cycleCount->items->count() }} Part</span>
                </div>
            </div>
        </div>

        <!-- Form Count Items Table -->
        <form method="POST" action="{{ route('cycle-counts.submit-count', $cycleCount) }}">
            @csrf

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Daftar Komponen & Hasil Hitung Fisik</h3>
                        <p class="text-[11px] text-slate-400">Masukkan angka hasil penghitungan fisik lapangan di kolom "Hitung Fisik Aktual"</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50/50 text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Foto & Part Number</th>
                                <th class="py-3 px-4">Nama Komponen</th>
                                <th class="py-3 px-4">Posisi Rak</th>
                                <th class="py-3 px-4 text-center">Stok Sistem</th>
                                <th class="py-3 px-4 text-center w-36">Hitung Fisik Aktual</th>
                                <th class="py-3 px-4 text-center">Selisih (Variance)</th>
                                <th class="py-3 px-4">Keterangan Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($cycleCount->items as $item)
                                <tr class="hover:bg-slate-50/60 transition" x-data="{
                                    sysQty: {{ (float) $item->system_qty }},
                                    physQty: {{ (float) $item->physical_qty }},
                                    get variance() {
                                        return this.physQty - this.sysQty;
                                    }
                                }">
                                    <!-- Photo & Part Number -->
                                    <td class="py-3 px-4">
                                        <div class="flex items-center space-x-2.5">
                                            <img src="{{ $item->component->image_url }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-white">
                                            <span class="font-mono font-bold text-slate-900 block">{{ $item->component->part_number }}</span>
                                        </div>
                                    </td>

                                    <!-- Name -->
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-slate-900 block max-w-xs truncate">{{ $item->component->name }}</span>
                                        <span class="text-[10px] text-slate-400">Satuan: {{ $item->component->uom }}</span>
                                    </td>

                                    <!-- Rack -->
                                    <td class="py-3 px-4 font-mono font-semibold text-slate-700">
                                        {{ $item->location->full_location_code }}
                                    </td>

                                    <!-- System Stock -->
                                    <td class="py-3 px-4 text-center font-bold text-slate-700 text-sm">
                                        {{ number_format($item->system_qty, 0) }}
                                    </td>

                                    <!-- Physical Input Field -->
                                    <td class="py-3 px-4 text-center">
                                        @if ($cycleCount->status === 'reconciled')
                                            <span class="font-extrabold text-slate-900 text-sm">{{ number_format($item->physical_qty, 0) }}</span>
                                        @else
                                            <input type="number" 
                                                   step="0.01" 
                                                   x-model.number="physQty" 
                                                   name="items[{{ $item->id }}][physical_qty]" 
                                                   class="w-24 text-center px-2 py-1.5 text-xs font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900">
                                        @endif
                                    </td>

                                    <!-- Variance -->
                                    <td class="py-3 px-4 text-center">
                                        <span class="font-bold text-sm" 
                                              :class="variance === 0 ? 'text-emerald-600' : (variance < 0 ? 'text-rose-600' : 'text-blue-600')"
                                              x-text="(variance > 0 ? '+' : '') + variance">
                                        </span>
                                    </td>

                                    <!-- Reason -->
                                    <td class="py-3 px-4">
                                        @if ($cycleCount->status === 'reconciled')
                                            <span class="text-slate-600 text-[11px]">{{ $item->variance_reason ?: '-' }}</span>
                                        @else
                                            <input type="text" 
                                                   name="items[{{ $item->id }}][variance_reason]" 
                                                   value="{{ $item->variance_reason }}"
                                                   placeholder="Alasan selisih (bila ada)..." 
                                                   class="w-full px-2.5 py-1 text-xs border border-slate-200 rounded-lg">
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($cycleCount->status !== 'reconciled')
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <p class="text-xs text-slate-500">Klik "Simpan Hasil Hitung Fisik" untuk mengunci angka fisik dan mengajukan ke Supervisor.</p>
                        <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow transition">
                            Simpan Hasil Hitung Fisik
                        </button>
                    </div>
                @endif
            </div>

        </form>

    </div>
</x-app-layout>
