<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Product Barcodes</title>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        @page {
            size: 40mm 60mm;
            margin: 5px;
        }
        @font-face {
            font-family: 'Exo 2';
            font-weight: 200;
            font-style: normal;
            src: url("{{ public_path('assets/fonts/static/Exo2-ExtraLight.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Exo 2';
            font-weight: 400;
            font-style: normal;
            src: url("{{ public_path('assets/fonts/static/Exo2-Regular.ttf') }}") format('truetype');
        }


        @font-face {
            font-family: 'Exo 2';
            font-weight: 600;
            font-style: normal;
            src: url("{{ public_path('assets/fonts/static/Exo2-SemiBold.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Exo 2';
            font-weight: 700;
            font-style: normal;
            src: url("{{ public_path('assets/fonts/static/Exo2-Bold.ttf') }}") format('truetype');
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Exo 2", sans-serif;
            line-height: 1
        }

        .barcode-page {
            width: 40mm;
            height: 60mm;
            display: flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            page-break-after: always;
            page-break-inside: avoid;
            break-inside: avoid;
            margin: 4px auto;
        }

        .barcode-page:last-child {
            page-break-after: avoid;
        }

        .barcode-card {
            width: 33mm;
            height: 52.4mm;
            padding: 2mm;
            border: 1px solid #000;
            border-radius: 12px;
            text-align: center;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        .qr-section {
            width: 100%;
        }

        .qr-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .qr-section td {
            vertical-align: middle;
        }

        .qr-img {
            width: 13mm;
            height: 13mm;
        }

        .scan-title {
            font-weight: 400;
            font-size: 9pt;
        }

        .scan-sub {
            font-size: 7pt;
            letter-spacing: 0.5px;
        }

        .product-name {
            font-weight: 500;
            font-size: 8pt;
            margin: 3.5mm 0;
            color: #000;
            line-height: 1.1;
        }

        .serial-big {
            font-size: 12pt;
            font-weight: 700;
            margin-top: 3mm;
            line-height: 1;
        }

        .serial-label {
            font-size: 6pt;
            color: #000;
            margin-top: 0.8mm;
        }

        .price {
            font-size: 8pt;
            margin: 2.2mm 0;
            line-height: 1;
        }

        .barcode-bottom {
            margin-top: 4.2mm;
        }

        .barcode-bottom img {
            width: 30mm;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .serial-small {
            font-size: 7pt;
            margin-top: 1.2mm;
            color: #000;
        }
    </style>
</head>

<body>
    @if (!empty($barcodes) && is_array($barcodes))
        @foreach ($barcodes as $item)
            <div class="barcode-card">
                <div class="qr-section">
                    <table>
                        <tr>
                            <td style="width:12mm; padding-right: 1mm;">
                                <img src="{{ $item['qrcode'] ?? '' }}" alt="QR" class="qr-img">
                            </td>
                            <td style="text-align: right;">
                                <div class="scan-title">SCAN TO</div>
                                <div class="scan-sub">REGISTER</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div>
                    <div class="product-name">Salamat Innovation</div>
                    <div class="serial-big">{{ $item['warranty'] ?? '' }}</div>
                    <div class="serial-label">Month warranty</div>
                    <div class="price"><strong>MRP</strong> -
                        BDT{{ number_format($item['price']['max_retail_price'] ?? 0, 2) }}</div>
                </div>

                <div class="barcode-bottom">
                    <img src="{{ $item['barcode'] ?? '' }}" alt="Barcode">
                    <div class="serial-small">{{ $item['serial_number'] ?? '' }}</div>
                </div>
            </div>
        @endforeach
    @else
        <p>No barcodes to print</p>
    @endif
</body>

</html>
