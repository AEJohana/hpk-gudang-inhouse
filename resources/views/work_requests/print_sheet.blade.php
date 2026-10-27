<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Work Request - {{ count($workRequests) === 1 ? $workRequests->first()->wri_number : 'Massal (' . count($workRequests) . ' Dokumen)' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm 7mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                background-color: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .a4-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-after: always;
                height: 100vh;
                max-height: 287mm;
                overflow: hidden;
            }
            .a4-page:last-child {
                page-break-after: avoid;
            }
        }

        .wr-table, .wr-table th, .wr-table td {
            border: 1.2px solid #000000;
            border-collapse: collapse;
        }

        .ttd-table, .ttd-table th, .ttd-table td {
            border: 1.2px solid #000000;
            border-collapse: collapse;
        }

        .slip-box {
            box-sizing: border-box;
            background: #ffffff;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-2 sm:p-6">

    <!-- Top Action Toolbar (No-Print) -->
    <div class="max-w-[210mm] mx-auto mb-4 p-3 bg-white border border-slate-300 rounded-2xl shadow-xs flex flex-wrap items-center justify-between gap-2 no-print">
        <div class="flex items-center space-x-2">
            <a href="{{ route('work-requests.print-queue') }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 transition flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali ke Antrean Cetak</span>
            </a>
            <span class="text-xs text-slate-500 font-medium">
                Total: <strong class="text-slate-900">{{ count($workRequests) }}</strong> Dokumen WR &bull; <strong>3 Duplikat / Halaman A4</strong>
            </span>
        </div>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-xl shadow-sm transition flex items-center space-x-1.5 cursor-pointer">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Sekarang (Print / PDF)</span>
            </button>
        </div>
    </div>

    <!-- DOCUMENT PAGES CONTAINER -->
    <div class="max-w-[210mm] mx-auto space-y-6">
        @foreach($workRequests as $pageIndex => $wri)
        @php
            $pemohonName = $wri->requestedBy?->name ?? 'Pemohon';
            
            $appPengajuLog = $wri->approval_pengaju_log;
            $kaPengajuName = $appPengajuLog?->operator_name 
                ?? ($wri->approval_pengaju === 'Diterima' ? ('Ka. ' . ($wri->dept_pengaju ?: 'Pengaju')) : '-');

            $operatorName = $wri->assigned_operator_name ?: '-';

            $appPenerimaLog = $wri->approval_penerima_log;
            $kaPenerimaName = $appPenerimaLog?->operator_name 
                ?? ($wri->approval_penerima === 'Diterima' ? ('Ka. ' . ($wri->dept_penerima ?: 'Mesin')) : '-');

            $qrDataUri = $wri->qr_code_data_uri;
            $printLogsCount = $wri->logs()->where('action_type', 'printed')->count();
            $isReprint = ($printLogsCount > 1);

            $slipLabels = [
                1 => ['code' => 'LEMBAR 1', 'desc' => 'PEMOHON / PENGAJU'],
                2 => ['code' => 'LEMBAR 2', 'desc' => 'PELAKSANA MESIN / OPERATOR'],
                3 => ['code' => 'LEMBAR 3', 'desc' => 'ARSIP GUDANG & MATERIAL HANDLING'],
            ];
        @endphp

        <!-- 1 HALAMAN HVS A4 (MEMUAT TEPAT 3 SLIP IDENTIK) -->
        <div class="a4-page bg-white p-2.5 sm:p-4 border sm:border-2 border-slate-300 sm:rounded-2xl shadow-sm flex flex-col justify-between" style="min-height: 282mm;">
            @for($slip = 1; $slip <= 3; $slip++)
            <!-- SLIP SATUAN (#{{ $slip }}) -->
            <div class="slip-box w-full flex flex-col justify-between" style="height: 87mm;">
                <!-- Header Slip -->
                <div class="flex items-start justify-between pb-1">
                    <!-- Kiri: Logo & Nama PT -->
                    <div class="flex items-center space-x-2">
                        <img src="{{ asset('images/logo_hpk.webp') }}" class="w-10 h-10 object-contain shrink-0" alt="HPK Logo">
                        <div>
                            <span class="text-[10px] font-black tracking-wider text-slate-900 block leading-tight">
                                PT. HYDRAXLE PERKASA
                            </span>
                            <span class="text-[7.5px] font-bold text-slate-500 uppercase tracking-widest block">
                                Karoseri & Manufacturing Inhouse
                            </span>
                        </div>
                    </div>

                    <!-- Tengah: Judul & No WRI -->
                    <div class="text-center flex-1 px-2">
                        <h1 class="text-sm font-black tracking-wide text-slate-900 underline uppercase leading-none">
                            WORK REQUEST
                        </h1>
                        <h2 class="text-[10px] font-extrabold tracking-wider text-slate-800 uppercase mt-0.5 leading-tight">
                            PERMINTAAN PEKERJAAN
                        </h2>
                        <div class="text-[9px] text-slate-900 mt-0.5 font-bold font-mono">
                            No <span class="border-b border-dotted border-slate-600 pb-0.5">{{ $wri->wri_number }}</span>
                        </div>
                    </div>

                    <!-- Kanan: QR Code Box ("Scan Here") & Label Lembar -->
                    <div class="flex items-start space-x-1.5 shrink-0">
                        <div class="text-right">
                            <span class="inline-block text-[7.5px] font-black text-slate-700 bg-slate-100 border border-slate-300 px-1 py-0.5 rounded leading-tight">
                                {{ $slipLabels[$slip]['code'] }}
                            </span>
                            @if($isReprint)
                                <span class="block text-[6.5px] font-black text-rose-600 uppercase mt-0.5">
                                    [Cetak Ulang]
                                </span>
                            @endif
                        </div>

                        <!-- Kotak Scan Here Presisi -->
                        <div class="border border-black rounded-xs overflow-hidden w-[58px] bg-white text-center shrink-0">
                            <div class="text-[7.5px] font-black uppercase text-slate-900 border-b border-black py-0.2 bg-slate-50 leading-tight">
                                Scan Here
                            </div>
                            <div class="p-0.5 flex items-center justify-center bg-white">
                                <img src="{{ $qrDataUri }}" class="w-[48px] h-[48px] object-contain mx-auto" alt="QR WRI">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data Komponen Presisi -->
                <table class="wr-table w-full text-[8.5px] text-slate-900 mt-0.5">
                    <tbody>
                        <tr>
                            <td class="font-bold py-0.5 px-1.5 w-[20%] bg-slate-50">Kode Komponen</td>
                            <td class="font-mono font-bold py-0.5 px-1.5 w-[30%]">{{ $wri->component?->part_number ?? '-' }}</td>
                            <td class="font-bold py-0.5 px-1.5 w-[20%] bg-slate-50">Tanggal Order</td>
                            <td class="py-0.5 px-1.5 w-[30%]">{{ $wri->created_at->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold py-0.5 px-1.5 bg-slate-50">Nama Komponen</td>
                            <td class="font-extrabold uppercase py-0.5 px-1.5 truncate">{{ $wri->component?->name ?? 'KOMPONEN' }}</td>
                            <td class="font-bold py-0.5 px-1.5 bg-slate-50">Qty</td>
                            <td class="font-extrabold py-0.5 px-1.5 text-xs">{{ number_format($wri->quantity_requested, 0) }} <span class="font-normal text-[8px] text-slate-600">{{ $wri->component?->uom ?? 'PCS' }}</span></td>
                        </tr>
                        <tr>
                            <td class="font-bold py-0.5 px-1.5 bg-slate-50">Dimensi</td>
                            <td class="py-0.5 px-1.5 text-[8px] text-slate-700 truncate">{{ $wri->component?->dimension_formatted ?: ($wri->component?->specification ?: '-') }}</td>
                            <td class="font-bold py-0.5 px-1.5 bg-slate-50">Dept Pengaju</td>
                            <td class="py-0.5 px-1.5 font-semibold">{{ $wri->dept_pengaju ?: 'Dept Gudang' }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-center font-bold text-[8px] bg-slate-50 py-0.2 uppercase tracking-wide">
                                Routing/Dept Penerima
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-center font-black text-[9px] py-0.5 tracking-wide text-slate-900 bg-white">
                                {{ $wri->routing_text ?: ($wri->dept_penerima ?: 'Pengerjaan Mesin In-House') }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Tabel 4 Tanda Tangan Resmi (Pemohon, Ka. Pengaju, Penerima/Operator, Ka. Penerima) -->
                <table class="ttd-table w-full text-center text-[8px] mt-0.5">
                    <thead>
                        <tr class="bg-slate-50 font-bold">
                            <th class="py-0.5 px-1 w-1/4">Pemohon</th>
                            <th class="py-0.5 px-1 w-1/4">Ka. Bagian Pemohon</th>
                            <th class="py-0.5 px-1 w-1/4">Penerima</th>
                            <th class="py-0.5 px-1 w-1/4">Ka. Bagian Penerima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Ruang Tanda Tangan / Paraf -->
                        <tr>
                            <td class="h-9 align-middle relative p-0.5">
                                @if($wri->requestedBy?->signature_path)
                                    <img src="{{ asset('storage/' . $wri->requestedBy->signature_path) }}" class="h-7 mx-auto object-contain" alt="TTD Pemohon">
                                @else
                                    <div class="text-[7.5px] text-slate-400 font-medium italic mt-2">[Paraf]</div>
                                @endif
                            </td>
                            <td class="h-9 align-middle relative p-0.5">
                                <span class="text-[7px] font-black text-emerald-800 bg-emerald-50 border border-emerald-300 px-1 py-0.2 rounded inline-block">
                                    [Disetujui]
                                </span>
                            </td>
                            <td class="h-9 align-middle relative p-0.5">
                                <div class="text-[7.5px] text-slate-400 font-medium italic mt-2">[Paraf OP]</div>
                            </td>
                            <td class="h-9 align-middle relative p-0.5">
                                <span class="text-[7px] font-black text-emerald-800 bg-emerald-50 border border-emerald-300 px-1 py-0.2 rounded inline-block">
                                    [Disetujui]
                                </span>
                            </td>
                        </tr>
                        <!-- Baris Nama Terang Pejabat -->
                        <tr class="font-bold text-[8px] bg-slate-50/50">
                            <td class="py-0.5 px-1 truncate">{{ $pemohonName }}</td>
                            <td class="py-0.5 px-1 truncate">{{ $kaPengajuName }}</td>
                            <td class="py-0.5 px-1 truncate text-indigo-900 font-black">{{ $operatorName }}</td>
                            <td class="py-0.5 px-1 truncate">{{ $kaPenerimaName }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- GARIS POTONG GUNTING ANTAR SLIP -->
            @if($slip < 3)
            <div class="my-1 border-t border-dashed border-slate-400 relative text-center">
                <span class="inline-block bg-white px-2 py-0.2 text-[7px] font-bold text-slate-400 uppercase tracking-widest relative -top-2">
                    <i class="fa-solid fa-scissors me-1 text-[8px]"></i> Potong di sini &bull; {{ $slipLabels[$slip]['desc'] }}
                </span>
            </div>
            @endif
            @endfor
        </div>
        @endforeach
    </div>

    <!-- Script Otomatis Buka Dialog Cetak Saat Halaman Dibuka -->
    <script>
        window.addEventListener('load', () => {
            // Beri jeda 400ms agar font, logo, dan QR Code ter-render sempurna sebelum dialog print muncul
            setTimeout(() => {
                window.print();
            }, 400);
        });
    </script>

</body>
</html>
