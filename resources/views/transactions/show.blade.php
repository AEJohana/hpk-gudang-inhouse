<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center space-x-3">
                <a href="{{ route('transactions.index') }}" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-slate-900 bg-slate-200 px-2 py-0.5 rounded">{{ $transaction->transaction_number }}</span>
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $transaction->type_badge['class'] }}">
                            {{ $transaction->type_badge['label'] }}
                        </span>
                    </div>
                    <h2 class="font-bold text-xl text-slate-900 leading-tight mt-1">
                        Rincian Mutasi Barang Gudang HPK
                    </h2>
                </div>
            </div>

            <!-- Print Slip Button -->
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center">
                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Bukti Mutasi (Slip)
            </button>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Slip Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 space-y-6" id="printable-slip">

            <!-- Slip Header -->
            <div class="flex items-center justify-between border-b-2 border-slate-900 pb-4">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo_hpk.webp') }}" alt="HPK" class="w-12 h-12 object-contain">
                    <div>
                        <h3 class="font-black text-lg text-slate-900 tracking-wide">PT HYDRAXLE PERKASA</h3>
                        <p class="text-xs font-semibold text-amber-600 uppercase">Karoseri & Hydraulic Systems - Warehouse Slip</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="font-mono text-sm font-bold text-slate-900 block">{{ $transaction->transaction_number }}</span>
                    <span class="text-xs text-slate-500">{{ $transaction->transaction_date->translatedFormat('d F Y') }}</span>
                </div>
            </div>

            <!-- Transaction Metadata -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
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

            <!-- Line Items Table -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Daftar Komponen & Posisi Rak:</h4>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
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
                                            <img src="{{ $item->component->image_url }}" class="w-9 h-9 rounded object-cover border border-slate-200 bg-white">
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

            <!-- Signatures Section for Karoseri Manufacturing Handover -->
            <div class="grid grid-cols-3 gap-4 pt-6 border-t border-slate-200 text-center text-xs text-slate-600">
                <div>
                    <p class="font-semibold text-slate-700">Dikeluarkan Oleh (Gudang):</p>
                    <div class="h-16"></div>
                    <p class="font-bold text-slate-900 border-t border-slate-300 pt-1 w-36 mx-auto">{{ $transaction->user->name }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-700">Diperiksa / QC Inspeksi:</p>
                    <div class="h-16"></div>
                    <p class="font-bold text-slate-900 border-t border-slate-300 pt-1 w-36 mx-auto">( Staff QC )</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-700">Diterima (Produksi Karoseri):</p>
                    <div class="h-16"></div>
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
            }
        }
    </style>
</x-app-layout>
