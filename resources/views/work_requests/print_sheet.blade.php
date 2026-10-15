<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Work Request - {{ $workRequest->wri_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        @page { size: A4; margin: 12mm; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 font-sans p-4 sm:p-8">

    <!-- Action Bar -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('work-requests.show', $workRequest) }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-xl transition flex items-center">
            <i class="fa-solid fa-arrow-left me-1.5"></i> Kembali ke Detail WRI
        </a>
        <button onclick="window.print()" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded-xl shadow-md transition flex items-center">
            <i class="fa-solid fa-print me-1.5"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    <!-- DOCUMENT CONTAINER (KARTU KERJA WORK REQUEST INHOUSE) -->
    <div class="max-w-4xl mx-auto bg-white border-2 border-slate-900 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">

        <!-- Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo_hpk.webp') }}" class="w-14 h-14 object-contain" alt="HPK Logo">
                <div>
                    <h1 class="text-lg font-black tracking-wider text-slate-900 uppercase leading-tight">PT. HYDRAXLE PERKASA</h1>
                    <h2 class="text-xs font-bold text-slate-600 tracking-wide uppercase">FORMULIR WORK REQUEST INHOUSE (WRI) &bull; KARTU KANBAN</h2>
                    <p class="text-[10px] text-slate-500">Divisi Fabrikasi Karoseri Inhouse &bull; WMS Hydraxle Perkasa</p>
                </div>
            </div>

            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white font-mono text-xs font-black rounded-lg">
                    {{ $workRequest->wri_number }}
                </span>
                <div class="text-[11px] text-slate-600 mt-1 font-semibold">
                    Tgl Order: <span class="text-slate-900 font-bold">{{ $workRequest->created_at->format('d M Y') }}</span>
                </div>
                @if($workRequest->spk_reference)
                <div class="text-[11px] text-blue-700 font-mono font-bold">
                    SPK: {{ $workRequest->spk_reference }}
                </div>
                @endif
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-300 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-500 block">Departemen Asal:</span>
                <span class="font-extrabold text-slate-900 text-sm">{{ $workRequest->dept_pengaju ?: 'Gudang' }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-500 block">Departemen Tujuan:</span>
                <span class="font-extrabold text-slate-900 text-sm">{{ $workRequest->dept_penerima ?: 'Mesin Shop' }}</span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-500 block">Prioritas:</span>
                <span class="font-extrabold uppercase {{ $workRequest->priority === 'urgent_line_stop' ? 'text-rose-600 font-black' : 'text-slate-900' }}">
                    {{ str_replace('_', ' ', $workRequest->priority) }}
                </span>
            </div>
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-500 block">Target Rak Gudang:</span>
                <span class="font-mono font-extrabold text-slate-900">{{ $workRequest->target_specific_location_code }}</span>
            </div>
        </div>

        <!-- Component Detail Table -->
        <div class="border border-slate-300 rounded-xl overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900 text-white uppercase text-[10px] font-bold">
                    <tr>
                        <th class="py-2.5 px-3">Kode / Part Number</th>
                        <th class="py-2.5 px-3">Nama Komponen</th>
                        <th class="py-2.5 px-3">Spesifikasi / Dimensi</th>
                        <th class="py-2.5 px-3 text-center">Jumlah Diminta</th>
                        <th class="py-2.5 px-3 text-center">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr>
                        <td class="py-3 px-3 font-mono font-bold text-slate-900">
                            {{ $workRequest->component->part_number }}
                        </td>
                        <td class="py-3 px-3 font-extrabold text-slate-900">
                            {{ $workRequest->component->name }}
                        </td>
                        <td class="py-3 px-3 text-slate-600 text-[11px]">
                            {{ $workRequest->component->specification ?: 'Standar Fabrikasi Karoseri HPK' }}
                        </td>
                        <td class="py-3 px-3 text-center font-black text-base text-slate-900">
                            {{ number_format($workRequest->quantity_requested, 0) }}
                        </td>
                        <td class="py-3 px-3 text-center font-bold text-slate-700">
                            {{ $workRequest->component->uom }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Alur Routing Mesin Fabrikasi -->
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-300 text-xs">
            <span class="text-[10px] uppercase font-bold text-slate-500 block mb-1">Tahapan Alur Mesin (Routing):</span>
            <div class="font-bold text-slate-900 text-sm flex items-center space-x-2">
                <i class="fa-solid fa-gears text-amber-600"></i>
                <span>{{ $workRequest->routing_text ?: 'Pengerjaan Mesin In-House' }}</span>
            </div>
            @if($workRequest->notes)
            <div class="mt-2 pt-2 border-t border-slate-200 text-slate-600 text-[11px]">
                <span class="font-bold">Catatan:</span> {{ $workRequest->notes }}
            </div>
            @endif
        </div>

        <!-- 4 Kotak Tanda Tangan Resmi (OP & Ka. Bagian Pengaju, OP & Ka. Bagian Penerima) -->
        <div>
            <span class="text-[10px] uppercase font-bold text-slate-500 block mb-2">Verifikasi & Pengesahan Otorisasi (Tanda Tangan Resmi):</span>
            <div class="grid grid-cols-4 gap-3 text-center text-xs">
                <!-- 1. Pemohon (OP Pengaju) -->
                <div class="border border-slate-300 rounded-xl p-3 flex flex-col justify-between h-36">
                    <span class="text-[10px] font-bold uppercase text-slate-500">Pemohon (OP)</span>
                    <div class="my-auto">
                        @if($workRequest->requestedBy && $workRequest->requestedBy->signature_path)
                            <img src="{{ asset('storage/' . $workRequest->requestedBy->signature_path) }}" class="h-12 mx-auto object-contain" alt="TTD Pemohon">
                        @else
                            <div class="h-10 border-b border-dashed border-slate-300 w-3/4 mx-auto"></div>
                        @endif
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 block text-xs underline">{{ $workRequest->requestedBy->name ?? 'Operator Pengaju' }}</span>
                        <span class="text-[9px] text-slate-400 block">{{ $workRequest->dept_pengaju ?: 'Gudang' }}</span>
                    </div>
                </div>

                <!-- 2. Ka. Bagian Pemohon -->
                <div class="border border-slate-300 rounded-xl p-3 flex flex-col justify-between h-36">
                    <span class="text-[10px] font-bold uppercase text-slate-500">Ka. Bagian Pemohon</span>
                    <div class="my-auto">
                        @if($workRequest->approval_pengaju === 'Diterima')
                            <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 inline-block mb-1">
                                [ Disetujui ]
                            </span>
                        @endif
                        <div class="h-10 border-b border-dashed border-slate-300 w-3/4 mx-auto"></div>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 block text-xs underline">Kepala Departemen</span>
                        <span class="text-[9px] text-slate-400 block">{{ $workRequest->dept_pengaju ?: 'Gudang' }}</span>
                    </div>
                </div>

                <!-- 3. Penerima (OP Mesin) -->
                <div class="border border-slate-300 rounded-xl p-3 flex flex-col justify-between h-36">
                    <span class="text-[10px] font-bold uppercase text-slate-500">Penerima (OP Mesin)</span>
                    <div class="my-auto">
                        <div class="h-10 border-b border-dashed border-slate-300 w-3/4 mx-auto"></div>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 block text-xs underline">Operator Mesin</span>
                        <span class="text-[9px] text-slate-400 block">{{ $workRequest->dept_penerima ?: 'Mesin Shop' }}</span>
                    </div>
                </div>

                <!-- 4. Ka. Bagian Penerima -->
                <div class="border border-slate-300 rounded-xl p-3 flex flex-col justify-between h-36">
                    <span class="text-[10px] font-bold uppercase text-slate-500">Ka. Bagian Penerima</span>
                    <div class="my-auto">
                        @if($workRequest->approval_penerima === 'Diterima')
                            <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 inline-block mb-1">
                                [ Disetujui ]
                            </span>
                        @endif
                        <div class="h-10 border-b border-dashed border-slate-300 w-3/4 mx-auto"></div>
                    </div>
                    <div>
                        <span class="font-bold text-slate-900 block text-xs underline">Kepala Mesin / Fabrikasi</span>
                        <span class="text-[9px] text-slate-400 block">{{ $workRequest->dept_penerima ?: 'Mesin Shop' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Barcode & Security Note -->
        <div class="pt-4 border-t border-slate-200 flex items-center justify-between text-[10px] text-slate-400">
            <span>Dicetak secara resmi melalui sistem WMS HPK pada {{ now()->format('d/m/Y H:i') }}</span>
            <span class="font-mono font-bold text-slate-600">SECURITY HASH: {{ strtoupper(substr(md5($workRequest->wri_number), 0, 16)) }}</span>
        </div>

    </div>

</body>
</html>
