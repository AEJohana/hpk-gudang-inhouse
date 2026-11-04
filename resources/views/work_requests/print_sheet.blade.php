<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Work Request - {{ count($workRequests) === 1 ? $workRequests->first()->wri_number : 'Massal (' . count($workRequests) . ' Dokumen)' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        @page {
            size: A4 portrait;
            margin: 4mm 8mm;
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
                margin: 0 auto !important;
                padding: 4mm 6mm !important;
                page-break-after: always;
                height: 100vh;
                max-height: 288mm;
                overflow: hidden;
                border: 1px solid #000000 !important;
            }
            .a4-page:last-child {
                page-break-after: avoid;
            }
        }

        .wr-table, .wr-table th, .wr-table td {
            border: 1px solid #000000;
            border-collapse: collapse;
        }

        .ttd-table, .ttd-table th, .ttd-table td {
            border: 1px solid #000000;
            border-collapse: collapse;
        }

        .slip-box {
            box-sizing: border-box;
            background: #ffffff;
            border: 1.2px solid #000000;
        }
    </style>
</head>
<body class="bg-slate-100 text-black font-sans p-2 sm:p-6">

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
        @endphp

        <!-- 1 HALAMAN HVS A4 (MEMUAT TEPAT 3 SLIP DIMAKSIMALKAN UKURANNYA) -->
        <div class="a4-page bg-white p-2.5 sm:p-4 border border-black shadow-sm flex flex-col justify-between" style="min-height: 284mm;">
            @for($slip = 1; $slip <= 3; $slip++)
            <!-- SLIP SATUAN (#{{ $slip }}) DENGAN TINGGI DIMAKSIMALKAN (90mm) & LEBAR PROPORSIONAL (max-w-[166mm]) -->
            <div class="slip-box w-full max-w-[166mm] mx-auto p-2.5 sm:p-3 flex flex-col justify-between bg-white" style="height: 90mm;">
                <!-- Header Slip: Posisi Naik Rapi ke Atas, 3 Kolom Simetris, Judul 100% DEAD CENTER -->
                <div class="grid grid-cols-[140px_1fr_140px] items-center w-full pt-0.5 pb-1">
                    <!-- Kiri: Logo HPK (Center dengan Nama Perusahaan & Diperbesar Lebih Jelas) -->
                    <div class="flex flex-col items-center justify-center text-center pl-1">
                        <img src="{{ asset('images/logo_hpk.png') }}" class="h-[46px] w-auto object-contain mx-auto" alt="HPK Logo">
                        <span class="text-[10px] font-black text-black tracking-normal uppercase mt-1 block leading-tight whitespace-nowrap">
                            PT. HYDRAXLE PERKASA
                        </span>
                    </div>

                    <!-- Tengah: Judul & No WRI (Posisi Naik, Lebih Besar & 100% DEAD CENTER) -->
                    <div class="text-center px-1">
                        <h1 class="text-[14.5px] font-black tracking-wide text-black underline uppercase leading-none">
                            WORK REQUEST
                        </h1>
                        <h2 class="text-[11.5px] font-bold tracking-wider text-black uppercase mt-1 leading-tight">
                            PERMINTAAN PEKERJAAN
                        </h2>
                        <div class="text-[9.5px] text-black mt-1.5 font-normal font-mono flex items-center justify-center">
                            <span class="font-normal mr-1.5">No</span>
                            <span class="border-b border-dotted border-black pb-0.5 px-2 inline-block min-w-[190px] text-center">
                                {{ $wri->wri_number }}
                            </span>
                        </div>
                    </div>

                    <!-- Kanan: Kotak Scan Here & QR Code Besar -->
                    <div class="flex justify-end pr-0.5">
                        <div class="w-[86px] border border-black bg-white text-center">
                            <div class="text-[8px] font-bold uppercase text-black border-b border-black py-0.5 leading-none bg-white">
                                Scan Here
                            </div>
                            <div class="p-1 flex items-center justify-center bg-white">
                                <img src="{{ $qrDataUri }}" class="w-[74px] h-[74px] object-contain mx-auto" alt="QR WRI">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data Komponen Presisi (Font Header Bold, Jawaban Reguler / Tidak Bold) -->
                <table class="wr-table w-full text-[8.5px] text-black bg-white mt-1.5">
                    <tbody>
                        <tr>
                            <td class="font-bold py-1 px-1.5 w-[18%]">Kode Komponen</td>
                            <td class="font-normal py-1 px-1.5 w-[32%]">{{ $wri->component?->part_number ?? '-' }}</td>
                            <td class="font-bold py-1 px-1.5 w-[18%]">Tanggal Order</td>
                            <td class="font-normal py-1 px-1.5 w-[32%]">{{ $wri->created_at->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold py-1 px-1.5">Nama Komponen</td>
                            <td class="font-normal py-1 px-1.5 truncate">{{ $wri->component?->name ?? 'KOMPONEN' }}</td>
                            <td class="font-bold py-1 px-1.5">Qty</td>
                            <td class="font-normal py-1 px-1.5">{{ number_format($wri->quantity_requested, 0) }}</td>
                        </tr>
                        <tr>
                            <td class="font-bold py-1 px-1.5">Dimensi</td>
                            <td class="font-normal py-1 px-1.5 truncate">{{ $wri->component?->dimension_formatted ?: ($wri->component?->specification ?: '') }}</td>
                            <td class="font-bold py-1 px-1.5">Dept Pengaju</td>
                            <td class="font-normal py-1 px-1.5">{{ $wri->dept_pengaju ?: 'Dept Gudang' }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-center font-bold py-0.5 leading-tight">
                                Routing/Dept Penerima
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-center font-normal py-1 leading-tight">
                                {{ $wri->routing_text ?: ($wri->dept_penerima ?: 'Pengerjaan Mesin In-House') }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Tabel 4 Tanda Tangan Resmi (Posisi Agak Mepet ke Atas, Ruang TTD Maksimal h-16 / ~64px) -->
                <table class="ttd-table w-full text-center text-[8.5px] text-black bg-white mt-1.5">
                    <thead>
                        <tr>
                            <th class="font-bold py-0.5 px-1 w-1/4">Pemohon</th>
                            <th class="font-bold py-0.5 px-1 w-1/4">Ka. Bagian Pemohon</th>
                            <th class="font-bold py-0.5 px-1 w-1/4">Penerima</th>
                            <th class="font-bold py-0.5 px-1 w-1/4">Ka. Bagian Penerima</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Ruang Tanda Tangan: Lebih Tinggi & Luas (h-16 / ~64px) untuk TTD Fisik Manual Pulpen -->
                        <tr>
                            <td class="h-16 align-middle relative p-0.5">
                                @if($wri->requestedBy?->signature_path)
                                    <img src="{{ asset('storage/' . $wri->requestedBy->signature_path) }}" class="h-12 mx-auto object-contain" alt="TTD Pemohon">
                                @endif
                            </td>
                            <td class="h-16 align-middle relative p-0.5"></td>
                            <td class="h-16 align-middle relative p-0.5"></td>
                            <td class="h-16 align-middle relative p-0.5"></td>
                        </tr>
                        <!-- Baris Nama Terang Pejabat: Font Normal (Tidak Bold) -->
                        <tr>
                            <td class="font-normal py-0.5 px-1 truncate">{{ $pemohonName ?: '-' }}</td>
                            <td class="font-normal py-0.5 px-1 truncate">{{ $kaPengajuName ?: '-' }}</td>
                            <td class="font-normal py-0.5 px-1 truncate">{{ $operatorName ?: '-' }}</td>
                            <td class="font-normal py-0.5 px-1 truncate">{{ $kaPenerimaName ?: '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- GARIS PEMISAH POTONG BERGUNTING UNTUK MEMPERMUDAH PEMOTONGAN -->
            @if($slip < 3)
            <div class="my-1 text-center relative border-t border-dashed border-slate-400">
                <span class="inline-block bg-white px-3 py-0.2 text-[7px] font-bold text-slate-500 uppercase tracking-widest relative -top-2">
                    <i class="fa-solid fa-scissors me-1 text-[8px]"></i> Potong di sini
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
