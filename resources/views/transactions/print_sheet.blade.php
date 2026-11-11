<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Slip Mutasi Gudang - {{ count($transactions) === 1 ? $transactions->first()->transaction_number : 'Massal (' . count($transactions) . ' Slip)' }}</title>
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        /* Standar Kertas A4 Portrait */
        @page {
            size: A4 portrait;
            margin: 8mm 10mm;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f1f5f9;
            background-image: none !important; /* Hilangkan background grid */
            color: #0f172a;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        @media print {
            body {
                background-color: #ffffff !important;
                background-image: none !important;
            }
            .no-print {
                display: none !important;
            }
            .page-container {
                box-shadow: none !important;
                margin: 0 auto !important;
                padding: 0 !important;
                page-break-after: always;
                min-height: 275mm;
                max-height: 280mm;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
            }
            .page-container:last-child {
                page-break-after: avoid;
            }
        }

        .slip-box {
            box-sizing: border-box;
            background: #ffffff;
            border: 1.5px solid #0f172a;
            border-radius: 6px;
        }

        .cut-guide {
            border-top: 1.5px dashed #94a3b8;
            position: relative;
            text-align: center;
        }

        .cut-guide span {
            position: relative;
            top: -10px;
            background: #ffffff;
            padding: 0 10px;
            font-size: 9px;
            font-family: monospace;
            color: #64748b;
            letter-spacing: 2px;
        }
    </style>
</head>
<body class="p-2 sm:p-6 print:p-0">

    <!-- Top Action Toolbar (No-Print) -->
    <div class="no-print max-w-4xl mx-auto mb-6 bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center space-x-3">
            <a href="{{ route('transactions.index') }}" 
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center space-x-2">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Transaksi</span>
            </a>
            <div>
                <h1 class="font-bold text-slate-900 text-sm sm:text-base">Pratinjau Cetak Slip Mutasi Gudang</h1>
                <p class="text-xs text-slate-500">
                    Total: <strong class="text-slate-800">{{ count($transactions) }} Slip</strong> terpilih &bull; 
                    <span class="text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        <i class="fa-solid fa-leaf me-1"></i>Hemat Kertas: Otomatis 2 Slip per 1 Lembar A4
                    </span>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-5 py-2 bg-[#0a2342] hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-sm transition flex items-center space-x-2 cursor-pointer">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Sekarang ({{ count($transactions) }} Slip)</span>
            </button>
        </div>
    </div>

    <!-- Container Halaman Cetak (Setiap Halaman A4 Memuat Maksimal 2 Slip) -->
    <div class="max-w-4xl mx-auto space-y-6 print:space-y-0">
        @foreach ($transactions->chunk(2) as $pageIndex => $pair)
            <div class="page-container bg-white p-4 sm:p-6 print:p-0 rounded-2xl print:rounded-none shadow-sm print:shadow-none border border-slate-200 print:border-0 mb-6 print:mb-0 space-y-4 print:space-y-3">
                
                @foreach ($pair as $slipIndex => $tx)
                    <!-- Slip Box -->
                    <div class="slip-box p-3.5 sm:p-4 print:p-3 space-y-2.5">
                        
                        <!-- Slip Header -->
                        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-2">
                            <div class="flex items-center space-x-2.5">
                                <img src="{{ asset('images/logo_hpk.webp') }}" alt="HPK" class="w-9 h-9 object-contain shrink-0">
                                <div>
                                    <h3 class="font-black text-sm text-slate-900 tracking-wide leading-tight">PT HYDRAXLE PERKASA</h3>
                                    <p class="text-[9px] font-semibold text-amber-600 uppercase tracking-wider">Karoseri &amp; Hydraulic Systems - Warehouse Slip</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono text-xs font-bold text-slate-900 block leading-tight">{{ $tx->transaction_number }}</span>
                                <span class="text-[10px] text-slate-500 font-medium">{{ $tx->transaction_date->translatedFormat('d F Y') }}</span>
                            </div>
                        </div>

                        <!-- Metadata Grid (4 Kolom Anti-Collision) -->
                        <div class="grid grid-cols-4 gap-2 text-[10px] bg-slate-50 p-2 rounded border border-slate-200">
                            <div class="min-w-0">
                                <span class="text-slate-400 block uppercase font-semibold text-[8px] tracking-wider">Tipe Transaksi</span>
                                <span class="font-bold text-slate-900 block truncate">{{ $tx->type_badge['label'] }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-slate-400 block uppercase font-semibold text-[8px] tracking-wider">No. SPK Karoseri</span>
                                <span class="font-mono font-bold text-blue-900 block truncate">{{ $tx->spk_number ?: '-' }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-slate-400 block uppercase font-semibold text-[8px] tracking-wider">Dokumen Acuan (PO/SJ)</span>
                                <span class="font-mono font-semibold text-slate-800 block text-[9px] leading-tight break-all">{{ $tx->reference_document ?: '-' }}</span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-slate-400 block uppercase font-semibold text-[8px] tracking-wider">Petugas Gudang</span>
                                <span class="font-bold text-slate-900 block truncate">{{ $tx->user->name }}</span>
                            </div>
                        </div>

                        @if ($tx->notes)
                            <div class="p-1.5 bg-slate-50 rounded border border-slate-200 text-[10px] text-slate-700 break-words">
                                <span class="font-bold text-slate-900">Catatan:</span> {{ $tx->notes }}
                            </div>
                        @endif

                        <!-- Items Table -->
                        <div class="border border-slate-200 rounded overflow-hidden">
                            <table class="w-full text-left text-[10px] text-slate-600">
                                <thead class="bg-slate-100 uppercase font-bold text-slate-700 text-[9px] border-b border-slate-200">
                                    <tr>
                                        <th class="py-1 px-2">Part Number &amp; Nama Komponen</th>
                                        <th class="py-1 px-2">
                                            @if (($tx->type ?? 'outbound') === 'inbound')
                                                Asal Pengiriman (Vendor/WR)
                                            @elseif (($tx->type ?? '') === 'outbound')
                                                Rak Asal Pengambilan
                                            @elseif (($tx->type ?? '') === 'transfer')
                                                Rak Asal
                                            @else
                                                Asal Retur
                                            @endif
                                        </th>
                                        <th class="py-1 px-2">
                                            @if (($tx->type ?? 'outbound') === 'inbound')
                                                Rak Penerimaan Gudang
                                            @elseif (($tx->type ?? '') === 'outbound')
                                                Tujuan Distribusi (Lini/SPK)
                                            @elseif (($tx->type ?? '') === 'transfer')
                                                Rak Tujuan Simpan
                                            @else
                                                Rak Penempatan Retur
                                            @endif
                                        </th>
                                        <th class="py-1 px-2 text-right">Kuantitas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach ($tx->items as $item)
                                        <tr>
                                            <td class="py-1.5 px-2">
                                                <div class="flex items-center space-x-2">
                                                    <img src="{{ $item->component->image_url }}" 
                                                         onerror="this.src='{{ asset('images/placeholder_part.svg') }}'"
                                                         class="w-6 h-6 rounded object-contain border border-slate-200 shrink-0 p-0.5">
                                                    <div class="min-w-0">
                                                        <span class="font-mono font-bold text-slate-900 block leading-tight">{{ $item->component->part_number }}</span>
                                                        <span class="text-[9px] text-slate-600 block truncate max-w-[220px]">{{ $item->component->name }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-1.5 px-2 font-mono text-[9px] break-words">
                                                @if (($tx->type ?? 'outbound') === 'inbound')
                                                    <span class="text-slate-800 font-semibold">{{ !empty($tx->is_from_work_request) ? ($tx->reference_document ?: 'Hasil Fabrikasi WR') : ($tx->reference_document ? 'PO/SJ: ' . $tx->reference_document : 'Supplier / Vendor Eksternal') }}</span>
                                                @elseif (($tx->type ?? '') === 'outbound')
                                                    {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang' }}
                                                @elseif (($tx->type ?? '') === 'transfer')
                                                    {{ $item->fromLocation ? $item->fromLocation->full_location_code : 'Gudang' }}
                                                @else
                                                    {{ $tx->reference_document ?: 'Lini Perakitan' }}
                                                @endif
                                            </td>
                                            <td class="py-1.5 px-2 font-mono text-[9px] break-words">
                                                @if (($tx->type ?? 'outbound') === 'inbound')
                                                    <span class="font-bold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 inline-block">{{ $item->toLocation ? $item->toLocation->full_location_code : 'Area Transit / Holding' }}</span>
                                                @elseif (($tx->type ?? '') === 'outbound')
                                                    <span class="text-blue-900 font-semibold">{{ $tx->spk_number ? ('SPK: ' . $tx->spk_number) : 'Lini Karoseri' }}</span>
                                                @elseif (($tx->type ?? '') === 'transfer')
                                                    <span class="font-bold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 inline-block">{{ $item->toLocation ? $item->toLocation->full_location_code : 'Gudang' }}</span>
                                                @else
                                                    <span class="font-bold text-purple-800 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200 inline-block">{{ $item->toLocation ? $item->toLocation->full_location_code : 'Area Retur' }}</span>
                                                @endif
                                            </td>
                                            <td class="py-1.5 px-2 text-right font-extrabold text-slate-900 whitespace-nowrap">
                                                {{ number_format($item->quantity, 0) }} {{ $item->component->uom }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Signatures Section: 2 Kolom Kontekstual -->
                        <div class="grid grid-cols-2 gap-6 pt-3 border-t border-slate-200 text-center text-[10px] text-slate-600">
                            @if ($tx->type === 'inbound')
                                <div>
                                    <p class="font-semibold text-slate-700">Diserahkan Oleh (Supplier / Kurir):</p>
                                    <div class="h-10"></div>
                                    <div class="border-b border-slate-400 w-36 mx-auto pb-0.5">
                                        <span class="text-[9px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-700">Diterima Oleh (Gudang):</p>
                                    <div class="h-10"></div>
                                    <p class="font-bold text-slate-900 border-b border-slate-400 pb-0.5 w-36 mx-auto truncate">{{ $tx->user->name }}</p>
                                </div>
                            @elseif ($tx->type === 'outbound')
                                <div>
                                    <p class="font-semibold text-slate-700">Dikeluarkan Oleh (Gudang):</p>
                                    <div class="h-10"></div>
                                    <p class="font-bold text-slate-900 border-b border-slate-400 pb-0.5 w-36 mx-auto truncate">{{ $tx->user->name }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-700">Diterima Oleh (Lini / User):</p>
                                    <div class="h-10"></div>
                                    <div class="border-b border-slate-400 w-36 mx-auto pb-0.5">
                                        <span class="text-[9px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                                    </div>
                                </div>
                            @elseif ($tx->type === 'transfer')
                                <div>
                                    <p class="font-semibold text-slate-700">Dipindahkan Oleh:</p>
                                    <div class="h-10"></div>
                                    <p class="font-bold text-slate-900 border-b border-slate-400 pb-0.5 w-36 mx-auto truncate">{{ $tx->user->name }}</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-700">Diverifikasi Oleh (Gudang):</p>
                                    <div class="h-10"></div>
                                    <div class="border-b border-slate-400 w-36 mx-auto pb-0.5">
                                        <span class="text-[9px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                                    </div>
                                </div>
                            @else
                                <div>
                                    <p class="font-semibold text-slate-700">Dikembalikan Oleh (Lini):</p>
                                    <div class="h-10"></div>
                                    <div class="border-b border-slate-400 w-36 mx-auto pb-0.5">
                                        <span class="text-[9px] text-slate-400 italic block whitespace-nowrap">( Nama Terang &amp; TTD )</span>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-700">Diterima Kembali (Gudang):</p>
                                    <div class="h-10"></div>
                                    <p class="font-bold text-slate-900 border-b border-slate-400 pb-0.5 w-36 mx-auto truncate">{{ $tx->user->name }}</p>
                                </div>
                            @endif
                        </div>

                    </div>

                    <!-- Garis Batas Potong di Tengah Halaman jika ada 2 slip dalam 1 lembar A4 -->
                    @if ($loop->first && count($pair) > 1)
                        <div class="cut-guide my-2">
                            <span>✂ POTONG DI SINI / TEAR HERE</span>
                        </div>
                    @endif

                @endforeach

            </div>
        @endforeach
    </div>

</body>
</html>
