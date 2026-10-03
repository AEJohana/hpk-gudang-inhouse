<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Thermal - {{ $qrRequest->request_number }} - PT Hydraxle Perkasa</title>
    <style>
        @page {
            size: 70mm 45mm;
            margin: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #000;
        }
        .page {
            width: 70mm;
            height: 45mm;
            padding: 3mm;
            box-sizing: border-box;
            page-break-after: always;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fff;
            margin: 0 auto 5mm auto;
            border: 1px dashed #ccc;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #000;
            padding-bottom: 1mm;
        }
        .logo-text {
            font-size: 7.5pt;
            font-weight: 900;
            letter-spacing: 0.3px;
        }
        .content {
            display: flex;
            align-items: center;
            margin-top: 1mm;
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
            font-size: 6pt;
            line-height: 1.25;
        }
        .part-num {
            font-family: 'Courier New', monospace;
            font-size: 8.5pt;
            font-weight: 900;
            background: #000;
            color: #fff;
            padding: 0.5mm 1mm;
            display: inline-block;
            margin-bottom: 1mm;
        }
        .part-name {
            font-size: 6.5pt;
            font-weight: bold;
            margin-bottom: 0.5mm;
            max-height: 8mm;
            overflow: hidden;
        }
        .footer {
            border-top: 1px solid #000;
            padding-top: 0.8mm;
            display: flex;
            justify-content: space-between;
            font-size: 5.5pt;
            font-weight: bold;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #fff;
            }
            .page {
                border: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 1000; background: #fff; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
        <span style="font-size: 12px; font-weight: bold; margin-right: 10px;">Total: {{ $qrRequest->print_qty }} Label Stiker</span>
        <button onclick="window.print()" style="background: #f59e0b; color: #000; font-weight: bold; font-size: 12px; padding: 6px 14px; border: none; border-radius: 6px; cursor: pointer;">
            🖨️ Cetak Printer Thermal
        </button>
        <button onclick="window.close()" style="background: #e2e8f0; font-size: 12px; padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; margin-left: 6px;">
            Tutup
        </button>
    </div>

    @for ($i = 1; $i <= $qrRequest->print_qty; $i++)
        <div class="page">
            <div class="header">
                <div>
                    <div class="logo-text">PT HYDRAXLE PERKASA</div>
                    <div style="font-size: 5pt; font-weight: bold; color: #555;">WMS KAROSERI IN-HOUSE</div>
                </div>
                <div style="font-size: 5.5pt; font-weight: bold;">
                    #{{ $i }}/{{ $qrRequest->print_qty }}
                </div>
            </div>

            <div class="content">
                <div class="qr-box">
                    {!! $qrRequest->component->qr_code_svg !!}
                </div>
                <div class="info-box">
                    <div class="part-num">{{ $qrRequest->component->part_number }}</div>
                    <div class="part-name">{{ $qrRequest->component->name }}</div>
                    <div><strong>Lokasi:</strong> {{ $qrRequest->component->defaultLocation ? $qrRequest->component->defaultLocation->full_location_code : 'Gudang HPK' }}</div>
                    <div><strong>Satuan:</strong> {{ $qrRequest->component->uom }}</div>
                </div>
            </div>

            <div class="footer">
                <span>{{ $qrRequest->request_number }}</span>
                <span>TGL: {{ date('d/m/Y') }}</span>
            </div>
        </div>
    @endfor

</body>
</html>
