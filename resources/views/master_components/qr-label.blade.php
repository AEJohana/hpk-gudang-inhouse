<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label QR - {{ $component->part_number }} - PT Hydraxle Perkasa</title>
    <style>
        @page {
            size: 70mm 45mm;
            margin: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 4mm;
            background: #fff;
            color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .label-container {
            width: 62mm;
            height: 37mm;
            border: 1px dashed #666;
            padding: 2mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #000;
            padding-bottom: 1mm;
        }
        .logo-text {
            font-size: 8pt;
            font-weight: 900;
            letter-spacing: 0.5px;
        }
        .sub-text {
            font-size: 5.5pt;
            font-weight: bold;
            color: #333;
        }
        .content {
            display: flex;
            align-items: center;
            margin-top: 1.5mm;
        }
        .qr-box {
            width: 22mm;
            height: 22mm;
            flex-shrink: 0;
        }
        .qr-box svg {
            width: 100%;
            height: 100%;
        }
        .info-box {
            padding-left: 2mm;
            flex: 1;
            font-size: 6.5pt;
            line-height: 1.2;
        }
        .part-num {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            font-weight: 900;
            background: #000;
            color: #fff;
            padding: 0.5mm 1mm;
            display: inline-block;
            margin-bottom: 1mm;
        }
        .part-name {
            font-size: 7pt;
            font-weight: bold;
            margin-bottom: 1mm;
            max-height: 8mm;
            overflow: hidden;
        }
        .footer {
            border-top: 1px solid #000;
            padding-top: 1mm;
            display: flex;
            justify-content: space-between;
            font-size: 6pt;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none;
            }
            .label-container {
                border: none;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000; background: #fff; padding: 8px; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <button onclick="window.print()" style="background: #f59e0b; color: #000; font-weight: bold; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer;">
            🖨️ Cetak Label Sekarang
        </button>
        <button onclick="window.close()" style="background: #eee; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; margin-left: 4px;">
            Tutup
        </button>
    </div>

    <div class="label-container">
        <div class="header">
            <div>
                <div class="logo-text">PT HYDRAXLE PERKASA</div>
                <div class="sub-text">KAROSERI WMS COMPONENT TAG</div>
            </div>
            <div style="font-size: 6pt; font-weight: bold;">
                {{ date('d/m/Y') }}
            </div>
        </div>

        <div class="content">
            <div class="qr-box">
                {!! $component->qr_code_svg !!}
            </div>
            <div class="info-box">
                <div class="part-num">{{ $component->part_number }}</div>
                <div class="part-name">{{ $component->name }}</div>
                <div><strong>Rak:</strong> {{ $component->defaultLocation ? $component->defaultLocation->full_location_code : 'ZONA UTAMA' }}</div>
                <div><strong>UoM:</strong> {{ $component->uom }}</div>
            </div>
        </div>

        <div class="footer">
            <span>ORIGINAL KAROSERI PART</span>
            <span>HYDRAXLE IN-HOUSE</span>
        </div>
    </div>

</body>
</html>
