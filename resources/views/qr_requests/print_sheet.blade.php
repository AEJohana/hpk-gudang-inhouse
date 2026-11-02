<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Lembar A4 - Label QR Komponen - PT Hydraxle Perkasa</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            color: #000;
        }
        .page-sheet {
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            padding: 10.2mm 8mm;
            margin: 0 auto 10mm auto;
            background: #fff;
            box-sizing: border-box;
            page-break-after: always;
            break-after: page;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }
        .page-sheet:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 0;
        }
        .labels-grid {
            display: grid;
            grid-template-columns: 95mm 95mm;
            gap: 2.4mm 4mm;
            width: 194mm;
            margin: 0 auto;
        }
        .label-wrapper {
            border: 1px solid #000;
            width: 95mm;
            height: 25.5mm;
            max-height: 25.5mm;
            box-sizing: border-box;
            display: flex;
            align-items: stretch;
            padding: 1.2mm;
            background: #fff;
            overflow: hidden;
        }
        /* Kotak Teks Informasi Komponen */
        .label-info-box {
            border: 1px solid #000;
            padding: 1mm 1.5mm;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
            min-width: 0;
        }
        .label-title {
            font-size: 6.5pt;
            font-weight: 900;
            font-family: Arial, sans-serif;
            letter-spacing: 0.2px;
            line-height: 1.1;
            margin-bottom: 0.8mm;
            text-transform: uppercase;
        }
        .label-fields-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 5.5pt;
            line-height: 1.25;
        }
        .label-fields-table tr {
            vertical-align: top;
        }
        .field-label {
            width: 21mm;
            white-space: nowrap;
            font-weight: normal;
            color: #000;
        }
        .field-colon {
            width: 2.5mm;
            text-align: center;
            font-weight: bold;
            color: #000;
        }
        .field-val {
            font-weight: bold;
            font-family: 'Courier New', Courier, monospace;
            font-size: 5.8pt;
            color: #000;
            word-break: break-all;
        }
        .field-val.name-text {
            font-family: Arial, sans-serif;
            font-size: 5.5pt;
            font-weight: bold;
            line-height: 1.15;
            max-height: 6.2mm;
            overflow: hidden;
            overflow-wrap: break-word;
            word-break: normal;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Kotak QR Code */
        .label-qr-box {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            align-self: center;
        }
        .label-qr-box svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* Format Khusus Label QR Rak (3 Blok: Teks, QR, Kotak Kosong) */
        .rack-label .label-qr-box {
            width: 21.5mm;
            height: 21.5mm;
            margin: 0 1.5mm;
        }
        .rack-empty-box {
            width: 16.5mm;
            border: 1px solid #000;
            flex-shrink: 0;
            background: #fff;
            box-sizing: border-box;
        }

        /* Format Khusus Label QR Kartu (2 Blok: Teks Lebih Lebar, QR) */
        .card-label .label-info-box {
            margin-right: 1.5mm;
        }
        .card-label .label-qr-box {
            width: 22.5mm;
            height: 22.5mm;
        }

        @media print {
            html, body {
                width: 210mm;
                height: 297mm;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .page-sheet {
                width: 210mm !important;
                height: 297mm !important;
                max-height: 297mm !important;
                padding: 10.2mm 8mm !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: always;
                break-after: page;
                overflow: hidden !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    @php
        // Normalisasi input single vs multiple
        $requests = isset($qrRequests) ? $qrRequests : collect([$qrRequest]);

        // Pisahkan tiket Rak dan tiket Kartu
        $rackTickets = $requests->filter(fn($q) => $q->label_type === 'rack');
        $cardTickets = $requests->filter(fn($q) => in_array($q->label_type, ['card', 'item', 'box']));

        // 1. Siapkan array fisik Label QR Rak (Lampiran 1 & 2)
        // 1 HVS = 20 label (bisa 2 qty atau 2 komponen @ 10 label)
        $rackLabels = [];
        foreach ($rackTickets as $ticket) {
            $comp = $ticket->component;
            if (!$comp) continue;

            $rackCode = ($comp->default_full_location_code !== '-') 
                ? $comp->default_full_location_code 
                : ($comp->defaultLocation ? $comp->defaultLocation->clean_rack_code : '');
            if ($rackCode === 'Gudang HPK' || $rackCode === '-') $rackCode = '';

            // Jika print_qty <= 2, 1 qty = 10 label (setengah lembar HVS)
            $count = ($ticket->print_qty <= 2) ? ($ticket->print_qty * 10) : $ticket->print_qty;

            for ($i = 0; $i < $count; $i++) {
                $rackLabels[] = [
                    'ticket' => $ticket,
                    'comp' => $comp,
                    'rack_code' => $rackCode,
                    'part_number' => $comp->part_number,
                    'name' => $comp->name,
                    'qr_svg' => $comp->qr_code_svg,
                ];
            }
        }

        // 2. Siapkan array fisik Label QR Kartu (Lampiran 3 & 4)
        $cardLabels = [];
        foreach ($cardTickets as $ticket) {
            $comp = $ticket->component;
            if (!$comp) continue;

            $rackCode = ($comp->default_full_location_code !== '-') 
                ? $comp->default_full_location_code 
                : ($comp->defaultLocation ? $comp->defaultLocation->clean_rack_code : '');
            if ($rackCode === 'Gudang HPK' || $rackCode === '-') $rackCode = '';

            $count = max(1, $ticket->print_qty);

            for ($i = 0; $i < $count; $i++) {
                $cardLabels[] = [
                    'ticket' => $ticket,
                    'comp' => $comp,
                    'rack_code' => $rackCode,
                    'part_number' => $comp->part_number,
                    'name' => $comp->name,
                    'qr_svg' => $comp->qr_code_svg,
                ];
            }
        }

        // Kelompokkan per 20 label per halaman A4
        $rackPages = array_chunk($rackLabels, 20);
        $cardPages = array_chunk($cardLabels, 20);
        $totalLabels = count($rackLabels) + count($cardLabels);
    @endphp

    <!-- Floating Top Bar Navigasi Cetak -->
    <div class="no-print" style="position: fixed; top: 15px; right: 15px; z-index: 1000; background: #fff; padding: 12px 18px; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px;">
        <div>
            <div style="font-size: 13px; font-weight: bold; color: #0f172a;">Pratinjau Dokumen Cetak Lembar A4</div>
            <div style="font-size: 11px; color: #64748b;">
                {{ $requests->count() }} Tiket Permintaan (Total: {{ $totalLabels }} Stiker Fisik)
            </div>
        </div>
        <button onclick="window.print()" style="background: #0284c7; color: #fff; font-weight: bold; font-size: 13px; padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
            🖨️ Cetak Dokumen
        </button>
        <button onclick="window.close()" style="background: #e2e8f0; color: #334155; font-size: 13px; font-weight: bold; padding: 8px 14px; border: none; border-radius: 8px; cursor: pointer;">
            Tutup
        </button>
    </div>

    <!-- 1. CETAK HALAMAN LABEL QR RAK (LAMPIRAN 1 & 2: GRID 20 / HVS DENGAN KOTAK KOSONG KANAN) -->
    @foreach ($rackPages as $pageIndex => $pageItems)
        <div class="page-sheet">
            <div class="labels-grid">
                @foreach ($pageItems as $label)
                    <div class="label-wrapper rack-label">
                        <!-- Blok Kiri: Kotak Teks Berbingkai -->
                        <div class="label-info-box">
                            <div class="label-title">KARTU QR KOMPONEN</div>
                            <table class="label-fields-table">
                                <tr>
                                    <td class="field-label">Kode Rak</td>
                                    <td class="field-colon">:</td>
                                    <td class="field-val">{{ $label['rack_code'] }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">Kode Komponen</td>
                                    <td class="field-colon">:</td>
                                    <td class="field-val">{{ $label['part_number'] }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">Nama Komponen</td>
                                    <td class="field-colon">:</td>
                                    <td class="field-val name-text">{{ $label['name'] }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Blok Tengah: QR Code SVG -->
                        <div class="label-qr-box">
                            {!! $label['qr_svg'] !!}
                        </div>

                        <!-- Blok Kanan: Kotak Kosong Berbingkai -->
                        <div class="rack-empty-box"></div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <!-- 2. CETAK HALAMAN LABEL QR KARTU (LAMPIRAN 3 & 4: GRID 2 KOLOM TANPA KOTAK KOSONG) -->
    @foreach ($cardPages as $pageIndex => $pageItems)
        <div class="page-sheet">
            <div class="labels-grid">
                @foreach ($pageItems as $label)
                    <div class="label-wrapper card-label">
                        <!-- Blok Kiri: Kotak Teks Berbingkai Lebih Lebar -->
                        <div class="label-info-box">
                            <div class="label-title">KARTU QR KOMPONEN</div>
                            <table class="label-fields-table">
                                <tr>
                                    <td class="field-label">Kode Rak</td>
                                    <td class="field-colon">:</td>
                                    <td class="field-val">{{ $label['rack_code'] }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">Kode Komponen</td>
                                    <td class="field-colon">:</td>
                                    <td class="field-val">{{ $label['part_number'] }}</td>
                                </tr>
                                <tr>
                                    <td class="field-label">Nama Komponen</td>
                                    <td class="field-colon">:</td>
                                    <td class="field-val name-text">{{ $label['name'] }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Blok Kanan: QR Code SVG -->
                        <div class="label-qr-box">
                            {!! $label['qr_svg'] !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

</body>
</html>
