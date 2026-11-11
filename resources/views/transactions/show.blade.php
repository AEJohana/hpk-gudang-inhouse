<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 w-full print:hidden">
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
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-[11px] font-bold border {{ $transaction->type_badge['class'] }}">
                            <i class="fa-solid {{ $transaction->type_badge['icon'] }} text-[10px]"></i>
                            <span>{{ $transaction->type_badge['label'] }}</span>
                        </span>
                    </div>
                    <h2 class="font-bold text-lg sm:text-xl text-slate-900 leading-tight mt-1 truncate">
                        Rincian Mutasi Barang Gudang HPK
                    </h2>
                </div>
            </div>

            <!-- Print Slip Button -->
            <button onclick="window.print()" class="w-full sm:w-auto px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center justify-center shrink-0 cursor-pointer">
                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Bukti Mutasi (Slip)</span>
            </button>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-3.5 sm:px-6 lg:px-8 py-6 space-y-6 print:p-0 print:m-0 print:max-w-none">

        <!-- Slip Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-8 space-y-6" id="printable-slip">

            <!-- Slip Header -->
            <div class="flex flex-col sm:flex-row print:flex-row sm:items-center print:items-center sm:justify-between print:justify-between border-b-2 border-slate-900 pb-4 print:pb-3 gap-3">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo_hpk.webp') }}" alt="HPK" class="w-11 h-11 sm:w-12 sm:h-12 print:w-11 print:h-11 object-contain shrink-0">
                    <div>
                        <h3 class="font-black text-base sm:text-lg print:text-base text-slate-900 tracking-wide">PT HYDRAXLE PERKASA</h3>
                        <p class="text-[11px] sm:text-xs print:text-[10px] font-semibold text-amber-600 uppercase">Karoseri & Hydraulic Systems - Warehouse Slip</p>
                    </div>
                </div>
                <div class="text-left sm:text-right print:text-right border-t sm:border-t-0 print:border-t-0 border-slate-100 pt-2 sm:pt-0 print:pt-0 shrink-0">
                    <span class="font-mono text-xs sm:text-sm print:text-xs font-bold text-slate-900 block">{{ $transaction->transaction_number }}</span>
                    <span class="text-[11px] sm:text-xs print:text-[10px] text-slate-500">{{ $transaction->transaction_date->translatedFormat('d F Y') }}</span>
                </div>
            </div>

            <!-- Transaction Metadata (Anti-Collision Grid with Subtle Background Framing) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 print:grid-cols-4 gap-3 sm:gap-4 print:gap-3 text-xs bg-slate-50/80 print:bg-slate-50/60 p-3.5 sm:p-4 print:p-3 rounded-xl border border-slate-200/80">
                <div class="min-w-0">
                    <span class="text-slate-400 block uppercase font-semibold text-[10px] tracking-wider">Tipe Transaksi</span>
                    <span class="font-bold text-slate-900 mt-0.5 flex items-center gap-1.5 truncate">
                        <i class="fa-solid {{ $transaction->type_badge['icon'] }} text-[11px] text-teal-600"></i>
                        <span>{{ $transaction->type_badge['label'] }}</span>
                    </span>
                </div>
                <div class="min-w-0">
                    <span class="text-slate-400 block uppercase font-semibold text-[10px] tracking-wider">No. SPK Karoseri</span>
                    <span class="font-mono font-bold text-blue-900 mt-0.5 block truncate">{{ $transaction->spk_number ?: '-' }}</span>
                </div>
                <div class="min-w-0">
                    <span class="text-slate-400 block uppercase font-semibold text-[10px] tracking-wider">Dokumen Acuan (PO/SJ/WRI)</span>
                    <span class="font-mono font-semibold text-slate-800 mt-0.5 block text-[11px] print:text-[10px] leading-tight break-all">
                        @if (!empty($transaction->is_from_work_request))
                            <span class="text-indigo-700 font-bold inline-flex items-center">
                                <i class="fa-solid fa-industry text-[9px] mr-1 text-indigo-500"></i>
                                {{ $transaction->reference_document ?: (!empty($transaction->workRequest) ? $transaction->workRequest->wri_number : '-') }}
                            </span>
                        @else
                            {{ $transaction->reference_document ?: '-' }}
                        @endif
                    </span>
                </div>
                <div class="min-w-0">
                    <span class="text-slate-400 block uppercase font-semibold text-[10px] tracking-wider">Petugas Gudang</span>
                    <span class="font-bold text-slate-900 mt-0.5 block truncate">{{ $transaction->user->name }}</span>
                </div>
            </div>

            @if ($transaction->notes)
                <div class="p-3 print:p-2.5 bg-slate-50 print:bg-slate-50/50 rounded-xl border border-slate-200 text-xs text-slate-700 break-words [overflow-wrap:anywhere]">
                    <span class="font-semibold text-slate-900">Catatan:</span> {{ $transaction->notes }}
                </div>
            @endif

            <!-- Line Items (Dual Mode: Mobile Cards & Desktop/Print Table) -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:mb-1.5 print:text-[10px]">Daftar Komponen & Posisi Rak:</h4>
                
                <!-- Mobile Card List (< sm screens) -->
                <div class="block sm:hidden space-y-2.5 print:hidden">
                    @foreach ($transaction->items as $item)
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-2.5">
                            <div class="flex items-start space-x-3">
                                <img src="{{ $item->component->image_url }}" 
                                     onerror="this.src='{{ asset('images/placeholder_part.svg') }}'"
                                     class="w-12 h-12 rounded-lg object-contain border border-slate-200 bg-white shrink-0 p-0.5">
                                <div class="min-w-0 flex-1">
                                    <span class="font-mono font-bold text-slate-900 block text-xs truncate">{{ $item->component->part_number }}</span>
                                    <span class="text-xs text-slate-700 font-semibold line-clamp-1">{{ $item->component->name }}</span>
                                </div>
                                <span class="font-black text-slate-900 text-sm shrink-0">
                                    {{ number_format($item->quantity, 0) }} <span class="text-xs font-normal text-slate-500">{{ $item->component->uom }}</span>
                                </span>
                            </div>
                            
                            <!-- Contextual Rack & Flow Display per Transaction Type -->
                            @if (($transaction->type ?? 'inbound') === 'inbound')
                                <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-[11px] bg-emerald-50/40 -mx-3.5 -mb-3.5 p-2.5 rounded-b-xl border-t border-emerald-100">
                                    <span class="text-slate-600 truncate mr-2">
                                        <i class="fa-solid fa-truck-ramp-box text-emerald-600 mr-1"></i>
                                        Asal: <strong class="text-slate-800 font-mono">{{ !empty($transaction->is_from_work_request) ? ($transaction->reference_document ?: 'Hasil WR') : ($transaction->reference_document ? 'PO/SJ: ' . $transaction->reference_document : 'Supplier / Vendor Eksternal') }}</strong>
                                    </span>
                                    <span class="text-emerald-800 font-mono font-bold shrink-0">
                                        <i class="fa-solid fa-warehouse text-emerald-600 mr-1"></i>
                                        Rak: {{ $item->toLocation ? $item->toLocation->full_location_code : 'Area Transit' }}
                                    </span>
                                </div>
                            @elseif (($transaction->type ?? '') === 'outbound')
                                <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-[11px] bg-blue-50/40 -mx-3.5 -mb-3.5 p-2.5 rounded-b-xl border-t border-blue-100">
                                    <span class="text-blue-800 font-mono font-bold truncate mr-2">
                                        <i class="fa-solid fa-box-open text-blue-600 mr-1"></i>
                                        Ambil: {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang' }}
                                    </span>
                                    <span class="text-slate-600 shrink-0">
                                        <i class="fa-solid fa-arrow-right text-slate-400 mr-1"></i>
                                        Tujuan: <strong class="text-slate-800 font-mono">{{ $transaction->spk_number ? ('SPK ' . $transaction->spk_number) : 'Lini Karoseri' }}</strong>
                                    </span>
                                </div>
                            @elseif (($transaction->type ?? '') === 'transfer')
                                <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-[11px] bg-amber-50/40 -mx-3.5 -mb-3.5 p-2.5 rounded-b-xl border-t border-amber-100 font-mono">
                                    <span class="text-slate-700 truncate mr-2">
                                        <span class="text-slate-400">Dari:</span> {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang' }}
                                    </span>
                                    <i class="fa-solid fa-arrow-right text-[10px] text-amber-500 mx-1"></i>
                                    <span class="text-amber-800 font-bold shrink-0">
                                        <span class="text-slate-400">Ke:</span> {{ $item->toLocation ? $item->toLocation->full_location_code : 'Gudang' }}
                                    </span>
                                </div>
                            @else
                                <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-[11px] bg-purple-50/40 -mx-3.5 -mb-3.5 p-2.5 rounded-b-xl border-t border-purple-100">
                                    <span class="text-slate-600 truncate mr-2">
                                        <i class="fa-solid fa-rotate-left text-purple-600 mr-1"></i>
                                        Asal: <strong class="text-slate-800 font-mono">{{ $transaction->reference_document ?: 'Lini' }}</strong>
                                    </span>
                                    <span class="text-purple-800 font-mono font-bold shrink-0">
                                        <i class="fa-solid fa-warehouse text-purple-600 mr-1"></i>
                                        Rak: {{ $item->toLocation ? $item->toLocation->full_location_code : 'Area Retur' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Desktop Table View (>= sm screens, Always visible when printing) -->
                <div class="hidden sm:block print:block border border-slate-200 rounded-xl overflow-x-auto print:overflow-visible">
                    <table class="w-full text-left text-xs text-slate-600 print:text-[11px]">
                        <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-200 print:bg-slate-100">
                            <tr>
                                <th class="py-2.5 px-3 print:py-1.5 print:px-2">Part Number & Nama Komponen</th>
                                <th class="py-2.5 px-3 print:py-1.5 print:px-2">
                                    @if (($transaction->type ?? 'inbound') === 'inbound')
                                        Asal Pengiriman (Vendor/WR)
                                    @elseif (($transaction->type ?? '') === 'outbound')
                                        Rak Asal Pengambilan
                                    @elseif (($transaction->type ?? '') === 'transfer')
                                        Rak Asal
                                    @else
                                        Asal Retur
                                    @endif
                                </th>
                                <th class="py-2.5 px-3 print:py-1.5 print:px-2">
                                    @if (($transaction->type ?? 'inbound') === 'inbound')
                                        Rak Penerimaan Gudang
                                    @elseif (($transaction->type ?? '') === 'outbound')
                                        Tujuan Distribusi (Lini/SPK)
                                    @elseif (($transaction->type ?? '') === 'transfer')
                                        Rak Tujuan Simpan
                                    @else
                                        Rak Penempatan Retur
                                    @endif
                                </th>
                                <th class="py-2.5 px-3 print:py-1.5 print:px-2 text-right">Kuantitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 print:divide-slate-200">
                            @foreach ($transaction->items as $item)
                                <tr class="print:break-inside-avoid">
                                    <td class="py-3 px-3 print:py-2 print:px-2">
                                        <div class="flex items-center space-x-2.5">
                                            <img src="{{ $item->component->image_url }}" 
                                                 onerror="this.src='{{ asset('images/placeholder_part.svg') }}'"
                                                 class="w-9 h-9 print:w-8 print:h-8 rounded object-contain border border-slate-200 bg-white shrink-0 p-0.5">
                                            <div class="min-w-0">
                                                <span class="font-mono font-bold text-slate-900 block text-xs print:text-[11px]">{{ $item->component->part_number }}</span>
                                                <span class="text-[11px] print:text-[10px] text-slate-600 block line-clamp-1 print:line-clamp-none">{{ $item->component->name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 print:py-2 print:px-2 font-mono text-[11px] print:text-[10px] break-words">
                                        @if (($transaction->type ?? 'inbound') === 'inbound')
                                            <span class="text-slate-800 font-semibold">{{ !empty($transaction->is_from_work_request) ? ($transaction->reference_document ?: 'Hasil Fabrikasi WR') : ($transaction->reference_document ? 'PO/SJ: ' . $transaction->reference_document : 'Supplier / Vendor Eksternal') }}</span>
                                        @elseif (($transaction->type ?? '') === 'outbound')
                                            {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang' }}
                                        @elseif (($transaction->type ?? '') === 'transfer')
                                            {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang' }}
                                        @else
                                            {{ $transaction->reference_document ?: 'Lini Perakitan' }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 print:py-2 print:px-2 font-mono text-[11px] print:text-[10px] break-words">
                                        @if (($transaction->type ?? 'inbound') === 'inbound')
                                            <span class="font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 inline-block">{{ $item->toLocation ? $item->toLocation->full_location_code : 'Area Transit / Holding' }}</span>
                                        @elseif (($transaction->type ?? '') === 'outbound')
                                            <span class="text-blue-900 font-semibold">{{ $transaction->spk_number ? ('SPK: ' . $transaction->spk_number) : 'Lini Karoseri' }}</span>
                                        @elseif (($transaction->type ?? '') === 'transfer')
                                            <span class="font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 inline-block">{{ $item->toLocation ? $item->toLocation->full_location_code : 'Gudang' }}</span>
                                        @else
                                            <span class="font-bold text-purple-800 bg-purple-50 px-2 py-0.5 rounded border border-purple-200 inline-block">{{ $item->toLocation ? $item->toLocation->full_location_code : 'Area Retur' }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 print:py-2 print:px-2 text-right font-extrabold text-slate-900 text-sm print:text-xs whitespace-nowrap">
                                        {{ number_format($item->quantity, 0) }} {{ $item->component->uom }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Signatures Section (Kontekstual Sesuai Jenis Transaksi) -->
            <div class="grid grid-cols-2 print:grid-cols-2 gap-4 sm:gap-8 print:gap-8 pt-6 print:pt-4 border-t border-slate-200 text-center text-xs text-slate-600 print:break-inside-avoid">
                @if (($transaction->type ?? 'inbound') === 'inbound')
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Diserahkan Oleh (Supplier / Kurir):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <div class="border-b border-slate-400 w-40 sm:w-48 mx-auto pb-1">
                            <span class="text-[11px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Diterima Oleh (Gudang):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <p class="font-bold text-slate-900 border-b border-slate-400 pb-1 w-40 sm:w-48 mx-auto truncate">{{ $transaction->user->name }}</p>
                    </div>
                @elseif (($transaction->type ?? '') === 'outbound')
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Diserahkan Oleh (Supplier / Kurir):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <div class="border-b border-slate-400 w-40 sm:w-48 mx-auto pb-1">
                            <span class="text-[11px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Diterima Oleh (Gudang):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <p class="font-bold text-slate-900 border-b border-slate-400 pb-1 w-40 sm:w-48 mx-auto truncate">{{ $transaction->user->name }}</p>
                    </div>
                @elseif ($transaction->type === 'outbound')
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Dikeluarkan Oleh (Gudang):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <p class="font-bold text-slate-900 border-b border-slate-400 pb-1 w-40 sm:w-48 mx-auto truncate">{{ $transaction->user->name }}</p>
                    </div>
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Diterima Oleh (Lini / User):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <div class="border-b border-slate-400 w-40 sm:w-48 mx-auto pb-1">
                            <span class="text-[11px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                        </div>
                    </div>
                @elseif ($transaction->type === 'transfer')
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Dipindahkan Oleh:</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <p class="font-bold text-slate-900 border-b border-slate-400 pb-1 w-40 sm:w-48 mx-auto truncate">{{ $transaction->user->name }}</p>
                    </div>
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Diverifikasi Oleh (Gudang):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <div class="border-b border-slate-400 w-40 sm:w-48 mx-auto pb-1">
                            <span class="text-[11px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                        </div>
                    </div>
                @else
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Dikembalikan Oleh (Lini):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <div class="border-b border-slate-400 w-40 sm:w-48 mx-auto pb-1">
                            <span class="text-[11px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                        </div>
                    </div>
                    <div class="p-3 bg-slate-50/80 sm:bg-transparent print:bg-transparent rounded-xl sm:rounded-none print:rounded-none border sm:border-0 print:border-0 border-slate-200">
                        <p class="font-semibold text-slate-700">Diterima Kembali (Gudang):</p>
                        <div class="h-12 sm:h-16 print:h-14"></div>
                        <p class="font-bold text-slate-900 border-b border-slate-400 pb-1 w-40 sm:w-48 mx-auto truncate">{{ $transaction->user->name }}</p>
                    </div>
                @endif
            </div>

        </div>

    </div>

    <style>
        /* Hapus background grid pada tampilan layar agar bersih dan polos */
        body {
            background-image: none !important;
            background-color: #f8fafc !important;
        }

        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }

        @media print {
            /* Sembunyikan seluruh komponen navigasi, header sistem, footer, dan tombol */
            nav, aside, header, footer, .no-print, [role="navigation"] {
                display: none !important;
            }

            html, body {
                background-image: none !important;
                background-color: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                font-size: 11pt !important;
                width: 100% !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Kotak Slip Mutasi Formal: Berbingkai tegas, padding proporsional, bebas dari margin terpotong */
            #printable-slip {
                position: static !important;
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
                padding: 6mm 8mm !important;
                border: 1.5px solid #0f172a !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                background-color: #ffffff !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</x-app-layout>
