<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full">
            <div class="flex items-center space-x-3">
                <a href="{{ route('transactions.index') }}" 
                   class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-2xs cursor-pointer shrink-0"
                   title="Kembali ke Riwayat Transaksi">
                    <i class="fa-solid fa-arrow-left text-xs text-slate-500"></i>
                    <span>Kembali</span>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-slate-900 bg-slate-200 px-2 py-0.5 rounded">{{ $transaction->transaction_number }}</span>
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $transaction->type_badge['class'] }}">
                            {{ $transaction->type_badge['label'] }}
                        </span>
                    </div>
                    <h2 class="font-bold text-lg sm:text-xl text-slate-900 leading-tight mt-1 truncate">
                        Rincian Mutasi Barang Gudang HPK
                    </h2>
                </div>
            </div>

            <!-- Print Slip Button -->
            <button onclick="window.print()" class="w-full sm:w-auto px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Bukti Mutasi (Slip)</span>
            </button>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-3.5 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Slip Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-8 space-y-6" id="printable-slip">

            <!-- Slip Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b-2 border-slate-900 pb-4 gap-3">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo_hpk.webp') }}" alt="HPK" class="w-11 h-11 sm:w-12 sm:h-12 object-contain shrink-0">
                    <div>
                        <h3 class="font-black text-base sm:text-lg text-slate-900 tracking-wide">PT HYDRAXLE PERKASA</h3>
                        <p class="text-[11px] sm:text-xs font-semibold text-amber-600 uppercase">Karoseri & Hydraulic Systems - Warehouse Slip</p>
                    </div>
                </div>
                <div class="text-left sm:text-right border-t sm:border-t-0 border-slate-100 pt-2 sm:pt-0">
                    <span class="font-mono text-xs sm:text-sm font-bold text-slate-900 block">{{ $transaction->transaction_number }}</span>
                    <span class="text-[11px] sm:text-xs text-slate-500">{{ $transaction->transaction_date->translatedFormat('d F Y') }}</span>
                </div>
            </div>

            <!-- Transaction Metadata -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">Tipe Transaksi</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $transaction->type_badge['label'] }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">No. SPK Karoseri</span>
                    <span class="font-mono font-bold text-blue-900 mt-0.5 block">{{ $transaction->spk_number ?: '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">Dokumen Acuan (PO/SJ)</span>
                    <span class="font-semibold text-slate-800 mt-0.5 block">{{ $transaction->reference_document ?: '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block uppercase font-semibold text-[10px]">Petugas Gudang</span>
                    <span class="font-bold text-slate-900 mt-0.5 block">{{ $transaction->user->name }}</span>
                </div>
            </div>

            @if ($transaction->notes)
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700">
                    <span class="font-semibold text-slate-900">Catatan:</span> {{ $transaction->notes }}
                </div>
            @endif

            <!-- Line Items (Dual Mode: Mobile Cards & Desktop/Print Table) -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Daftar Komponen & Posisi Rak:</h4>
                
                <!-- Mobile Card List (< sm screens) -->
                <div class="block sm:hidden space-y-2.5 print:hidden">
                    @foreach ($transaction->items as $item)
                        <div class="p-3 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2">
                            <div class="flex items-start space-x-2.5">
                                <img src="{{ $item->component->image_url }}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-white shrink-0">
                                <div class="min-w-0 flex-1">
                                    <span class="font-mono font-bold text-slate-900 block text-xs truncate">{{ $item->component->part_number }}</span>
                                    <span class="text-xs text-slate-700 font-semibold line-clamp-1">{{ $item->component->name }}</span>
                                </div>
                                <span class="font-black text-slate-900 text-sm shrink-0">
                                    {{ number_format($item->quantity, 0) }} <span class="text-xs font-normal text-slate-500">{{ $item->component->uom }}</span>
                                </span>
                            </div>
                            <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px] font-mono text-slate-600">
                                <span><span class="text-slate-400">Dari:</span> {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang' }}</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                <span><span class="text-slate-400">Ke:</span> {{ $item->toLocation ? $item->toLocation->full_location_code : 'Lini' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table View (>= sm screens, Always visible when printing) -->
                <div class="hidden sm:block print:block border border-slate-200 rounded-xl overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3">Part Number & Nama Komponen</th>
                                <th class="py-2.5 px-3">Rak Asal</th>
                                <th class="py-2.5 px-3">Rak Tujuan</th>
                                <th class="py-2.5 px-3 text-right">Kuantitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($transaction->items as $item)
                                <tr>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center space-x-2.5">
                                            <img src="{{ $item->component->image_url }}" class="w-9 h-9 rounded object-cover border border-slate-200 bg-white shrink-0">
                                            <div>
                                                <span class="font-mono font-bold text-slate-900 block text-xs">{{ $item->component->part_number }}</span>
                                                <span class="text-[11px] text-slate-600">{{ $item->component->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 font-mono text-[11px]">
                                        {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang Luar / Vendor' }}
                                    </td>
                                    <td class="py-3 px-3 font-mono text-[11px]">
                                        {{ $item->toLocation ? $item->toLocation->full_location_code : 'Lini Perakitan Karoseri' }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-extrabold text-slate-900 text-sm">
                                        {{ number_format($item->quantity, 0) }} {{ $item->component->uom }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signatures Section for Karoseri Manufacturing Handover (Mobile 1 col, Desktop/Print 3 cols) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 print:grid-cols-3 gap-4 pt-6 border-t border-slate-200 text-center text-xs text-slate-600">
                <div class="p-3 bg-slate-50 sm:bg-transparent rounded-xl sm:rounded-none border sm:border-0 border-slate-200">
                    <p class="font-semibold text-slate-700">Dikeluarkan Oleh (Gudang):</p>
                    <div class="h-12 sm:h-16"></div>
                    <p class="font-bold text-slate-900 border-t border-slate-300 pt-1 w-36 mx-auto">{{ $transaction->user->name }}</p>
                </div>
                <div class="p-3 bg-slate-50 sm:bg-transparent rounded-xl sm:rounded-none border sm:border-0 border-slate-200">
                    <p class="font-semibold text-slate-700">Diperiksa / QC Inspeksi:</p>
                    <div class="h-12 sm:h-16"></div>
                    <p class="font-bold text-slate-900 border-t border-slate-300 pt-1 w-36 mx-auto">( Staff QC )</p>
                </div>
                <div class="p-3 bg-slate-50 sm:bg-transparent rounded-xl sm:rounded-none border sm:border-0 border-slate-200">
                    <p class="font-semibold text-slate-700">Diterima (Produksi Karoseri):</p>
                    <div class="h-12 sm:h-16"></div>
                    <p class="font-bold text-slate-900 border-t border-slate-300 pt-1 w-36 mx-auto">( Leader Assembly )</p>
                </div>
            </div>

        </div>

    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-slip, #printable-slip * {
                visibility: visible;
            }
            #printable-slip {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</x-app-layout>
