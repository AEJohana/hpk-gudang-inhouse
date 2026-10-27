<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
            <div class="flex items-center space-x-3">
                <a href="{{ route('cycle-counts.index') }}" class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                    <span>Kembali</span>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-amber-900 bg-amber-100 px-2 py-0.5 rounded">{{ $cycleCount->count_number }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $cycleCount->status_badge['class'] }}">
                            {{ $cycleCount->status_badge['label'] }}
                        </span>
                    </div>
                    <h2 class="font-bold text-lg sm:text-xl text-slate-900 leading-tight mt-1">
                        Lembar Penghitungan Fisik - ZONA {{ $cycleCount->zone_target }}
                    </h2>
                </div>
            </div>

            <!-- Reconciliation Action for Supervisor -->
            @if ($cycleCount->status === 'pending_review')
                <form method="POST" action="{{ route('cycle-counts.reconcile', $cycleCount) }}" onsubmit="return confirm('Apakah Anda menyetujui hasil stok opname ini? Seluruh saldo stok fisik di sistem akan disesuaikan secara otomatis!')" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center justify-center">
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
        <form method="POST" 
              action="{{ route('cycle-counts.submit-count', $cycleCount) }}"
              x-data="{
                  counts: {
                      @foreach ($cycleCount->items as $item)
                          {{ $item->id }}: {
                              sysQty: {{ (float) $item->system_qty }},
                              physQty: {{ (float) $item->physical_qty }},
                              reason: @js($item->variance_reason ?? '')
                          },
                      @endforeach
                  }
              }">
            @csrf

            <!-- Hidden Inputs for actual form submission -->
            @foreach ($cycleCount->items as $item)
                <input type="hidden" name="items[{{ $item->id }}][physical_qty]" :value="counts[{{ $item->id }}].physQty">
                <input type="hidden" name="items[{{ $item->id }}][variance_reason]" :value="counts[{{ $item->id }}].reason">
            @endforeach

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Daftar Komponen & Hasil Hitung Fisik</h3>
                        <p class="text-[11px] text-slate-400">Masukkan angka hasil penghitungan fisik lapangan di kolom "Hitung Fisik Aktual"</p>
                    </div>
                </div>

                <!-- 1. Mobile Card List View (< md screens) -->
                <div class="block md:hidden p-3.5 space-y-4">
                    @foreach ($cycleCount->items as $item)
                    <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-3">
                        <!-- Part info & Rack badge -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center space-x-2.5">
                                <img src="{{ $item->component->image_url }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 bg-white shrink-0">
                                <div class="min-w-0">
                                    <span class="font-mono font-bold text-xs text-slate-900 block truncate">{{ $item->component->part_number }}</span>
                                    <span class="font-bold text-slate-800 text-xs line-clamp-1">{{ $item->component->name }}</span>
                                    <span class="text-[10px] text-slate-400">Satuan: {{ $item->component->uom }}</span>
                                </div>
                            </div>
                            <span class="font-mono font-bold text-xs text-blue-900 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg shrink-0">
                                <i class="fa-solid fa-location-dot text-rose-500 me-1"></i>{{ $item->location->full_location_code }}
                            </span>
                        </div>

                        <!-- Stok Sistem vs Selisih -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 grid grid-cols-2 gap-3 items-center">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Stok Sistem:</span>
                                <span class="text-base font-black text-slate-800">
                                    {{ number_format($item->system_qty, 0) }} <span class="text-xs font-normal text-slate-500">{{ $item->component->uom }}</span>
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Selisih:</span>
                                <span class="text-sm font-extrabold flex items-center gap-1"
                                      :class="(counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) === 0 ? 'text-emerald-600' : ((counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) < 0 ? 'text-rose-600' : 'text-blue-600')">
                                    <span x-text="((counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) > 0 ? '+' : '') + (counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty)"></span>
                                    <span class="text-[10px] font-normal" x-text="(counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) === 0 ? '(Sesuai)' : '{{ $item->component->uom }}'"></span>
                                </span>
                            </div>
                        </div>

                        <!-- Stepper / Input Hitung Fisik -->
                        @if ($cycleCount->status === 'reconciled')
                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="font-bold text-slate-700">Hasil Fisik Terkunci:</span>
                                <span class="font-black text-base text-slate-900">{{ number_format($item->physical_qty, 0) }} {{ $item->component->uom }}</span>
                            </div>
                            @if($item->variance_reason)
                                <div class="text-[11px] text-slate-600 bg-slate-100 p-2 rounded-lg">
                                    <span class="font-bold text-slate-700">Alasan:</span> {{ $item->variance_reason }}
                                </div>
                            @endif
                        @else
                            <div>
                                <label class="block text-[11px] font-extrabold text-slate-700 uppercase tracking-wider mb-1">
                                    Hitung Fisik Aktual <span class="text-rose-500">*</span>
                                </label>
                                <div class="flex items-center rounded-xl border-2 border-slate-200 bg-white focus-within:border-amber-500 p-1">
                                    <button type="button" 
                                            @click="if(counts[{{ $item->id }}].physQty > 0) counts[{{ $item->id }}].physQty--"
                                            class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           step="any" 
                                           inputmode="decimal"
                                           x-model.number="counts[{{ $item->id }}].physQty" 
                                           class="flex-1 text-center text-xl font-black text-slate-900 border-0 focus:ring-0 p-0 font-mono">
                                    <button type="button" 
                                            @click="counts[{{ $item->id }}].physQty++"
                                            class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Variance Reason Input (Visible if variance != 0) -->
                            <div x-show="(counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) !== 0" x-transition>
                                <label class="block text-[10px] font-bold text-amber-700 uppercase tracking-wider mb-1">
                                    Keterangan / Alasan Selisih:
                                </label>
                                <input type="text" 
                                       x-model="counts[{{ $item->id }}].reason" 
                                       placeholder="Contoh: Salah hitung lot lama, barang tercecer..."
                                       class="w-full px-3 py-1.5 text-xs border border-amber-300 bg-amber-50/40 rounded-xl focus:ring-2 focus:ring-amber-500 focus:bg-white">
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- 2. Desktop Table View (>= md screens) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-xs text-slate-600">
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
                                <tr class="hover:bg-slate-50/60 transition">
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
                                                   step="any" 
                                                   inputmode="decimal"
                                                   x-model.number="counts[{{ $item->id }}].physQty" 
                                                   class="w-24 text-center px-2 py-1.5 text-xs font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900">
                                        @endif
                                    </td>

                                    <!-- Variance -->
                                    <td class="py-3 px-4 text-center">
                                        <span class="font-bold text-sm" 
                                              :class="(counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) === 0 ? 'text-emerald-600' : ((counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) < 0 ? 'text-rose-600' : 'text-blue-600')"
                                              x-text="((counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty) > 0 ? '+' : '') + (counts[{{ $item->id }}].physQty - counts[{{ $item->id }}].sysQty)">
                                        </span>
                                    </td>

                                    <!-- Reason -->
                                    <td class="py-3 px-4">
                                        @if ($cycleCount->status === 'reconciled')
                                            <span class="text-slate-600 text-[11px]">{{ $item->variance_reason ?: '-' }}</span>
                                        @else
                                            <input type="text" 
                                                   x-model="counts[{{ $item->id }}].reason" 
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
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <p class="text-xs text-slate-500 text-center sm:text-left">Klik "Simpan Hasil Hitung Fisik" untuk mengunci angka fisik dan mengajukan ke Supervisor.</p>
                        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs rounded-xl shadow transition">
                            Simpan Hasil Hitung Fisik
                        </button>
                    </div>
                @endif
            </div>

        </form>

    </div>
</x-app-layout>
