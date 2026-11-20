<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Exo 2 Print Label</title>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;600;700;800&display=swap" rel="stylesheet" />

    <style>
        /* PDF Print Configuration */
        @page {
            size: 60mm 40mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Exo 2", sans-serif;
            background-color: #fff;
            /* Removed grey background for printing */
            width: 60mm;
            height: 40mm;
        }

        .label-container {
            width: 60mm;
            height: 40mm;
            padding: 2mm 3mm;
            box-sizing: border-box;
            text-align: center;
            overflow: hidden;
            margin: 0 auto;
            /* Flexbox removed because Dompdf doesn't support it well */
        }

        /* Brand Header */
        .brand-header {
            width: 100%;
            margin-top: 1mm;
        }

        .brand-name {
            font-size: 12pt;
            font-weight: 600;
            letter-spacing: 0.2mm;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.1;
        }

        .horizontal-rule {
            width: 95%;
            border-bottom: 0.2mm solid #000;
            /* Used border instead of background-div */
            margin: 1mm auto;
        }

        /* Product Details */
        .product-section {
            margin-top: 2mm;
        }

        .product-title {
            font-size: 10pt;
            font-weight: 600;
            margin: 0;
            color: #000;
            line-height: 1.2;
        }

        .product-variant {
            font-size: 8pt;
            font-weight: 600;
            margin: 0.5mm 0;
            color: #000;
        }

        /* Price */
        .price-tag {
            font-size: 8pt;
            font-weight: 600;
            margin: 1.5mm 0;
            letter-spacing: -0.2mm;
        }

        /* Barcode Area */
        .barcode-wrapper {
            width: 100%;
            margin-top: 1mm;
        }

        .barcode-id {
            color: #000;
            font-size: 8pt;
            font-weight: 600;
            margin-top: 1mm;
            letter-spacing: 0.4mm;
        }

        .brand-header,
        .brand-name,
        .product-section,
        .product-title,
        .product-variant,
        .price-tag,
        .barcode-wrapper,
        .barcode-image,
        .barcode-id {
            display: block;
            margin-left: auto;
            margin-right: auto;
            /* center block elements */
            text-align: center;
            /* ensure text is centered */
        }

        .barcode-image {
            width: 90%;
            display: block;
            margin: 1mm auto 0 auto;
            /* top margin + center */

        }
    </style>
</head>

<body>
    @if(!empty($barcodes))

    @foreach($barcodes as $item)

    <div class="label-container">
        <!--<div class="brand-header">-->
        <!--    <div class="brand-name">Salamat Innovation</div>-->
        <!--    <div class="horizontal-rule"></div>-->
        <!--</div>-->
        <div class="product-section">
            <div class="product-title">{{$item['product_name']}}</div>
            <div class="product-variant">Variant: {{ $item['attribute_values'] ?? '' }}</div>
        </div>

        <div class="price-tag">MRP - {{ number_format($item['price'] ?? 0, 2) }}</div>

        <div class="barcode-wrapper">
            <img class="barcode-image" src="{{ $item['barcode'] ?? '' }}" alt="Barcode">
            <div class="barcode-id">{{ $item['serial_number'] ?? '' }} </div>
        </div>
    </div>
    @endforeach
    @endif

    <script>
        window.onload = function() {

            window.print();

            window.onafterprint = function() {
                window.close();
            };

        };
    </script>
</body>

</html>