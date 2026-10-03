<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Lembar A4 - {{ $qrRequest->request_number }} - PT Hydraxle Perkasa</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 10mm;
            background: #f1f5f9;
            color: #000;
        }
        .sheet-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4mm;
            background: #fff;
            padding: 5mm;
            max-width: 190mm;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .label-card {
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 3mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 40mm;
            background: #fff;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
        }
        .logo-title {
            font-size: 6.5pt;
            font-weight: 900;
        }
        .content {
            display: flex;
            align-items: center;
            margin: 1.5mm 0;
        }
        .qr-box {
            width: 18mm;
            height: 18mm;
            flex-shrink: 0;
        }
        .qr-box svg {
            width: 100%;
            height: 100%;
        }
        .info-box {
            padding-left: 2mm;
            flex: 1;
            font-size: 5.5pt;
            line-height: 1.2;
        }
        .part-num {
            font-family: 'Courier New', monospace;
            font-size: 7.5pt;
            font-weight: 900;
            background: #000;
            color: #fff;
            padding: 0.5mm 1mm;
            display: inline-block;
            margin-bottom: 0.8mm;
        }
        .part-name {
            font-size: 6pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
            max-height: 7mm;
            overflow: hidden;
        }
        .footer {
            border-top: 1px solid #000;
            padding-top: 0.8mm;
            display: flex;
            justify-content: space-between;
            font-size: 5pt;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff;
                padding: 0;
            }
            .sheet-container {
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 15px; right: 15px; z-index: 1000; background: #fff; padding: 12px 18px; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
        <span style="font-size: 13px; font-weight: bold; margin-right: 12px;">Format Lembar Kertas A4 (Grid 3 Kolom)</span>
        <button onclick="window.print()" style="background: #0284c7; color: #fff; font-weight: bold; font-size: 13px; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">
            🖨️ Cetak Lembar A4
        </button>
        <button onclick="window.close()" style="background: #e2e8f0; font-size: 13px; padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; margin-left: 6px;">
            Tutup
        </button>
    </div>

    <div class="sheet-container">
        @for ($i = 1; $i <= $qrRequest->print_qty; $i++)
            <div class="label-card">
                <div class="header">
                    <span class="logo-title">PT HYDRAXLE PERKASA</span>
                    <span style="font-size: 5pt; font-weight: bold;">#{{ $i }}</span>
                </div>

                <div class="content">
                    <div class="qr-box">
                        {!! $qrRequest->component->qr_code_svg !!}
                    </div>
                    <div class="info-box">
                        <div class="part-num">{{ $qrRequest->component->part_number }}</div>
                        <div class="part-name">{{ $qrRequest->component->name }}</div>
                        <div><strong>Rak:</strong> {{ $qrRequest->component->defaultLocation ? $qrRequest->component->defaultLocation->rack_number : 'Gudang' }}</div>
                        <div><strong>Satuan:</strong> {{ $qrRequest->component->uom }}</div>
                    </div>
                </div>

                <div class="footer">
                    <span>ORIGINAL HPK</span>
                    <span>{{ date('d/m/y') }}</span>
                </div>
            </div>
        @endfor
    </div>

</body>
</html>
