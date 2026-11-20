<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Stock Report</title>
    <style>
        /* Without this, each th/td's padding adds on top of its declared
           percentage width instead of being included in it — with 12
           columns those extra pixels add up fast and squeeze/clip the
           later column headers (e.g. "Total"). */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #334155;
            margin: 0;
            padding: 10px;
            line-height: 1.2;
        }

        /* Prevent page break inside rows if possible */
        tr {
            page-break-inside: avoid;
        }

        table {
            page-break-inside: auto;
            width: 100%;
            border-collapse: collapse;
        }

        /* Without table-layout: fixed, columns auto-size to their content —
           the long serial numbers were forcing that column wider than its
           declared %, pushing Total/Sale/In Stock off the page entirely. */
        .stock-table {
            table-layout: fixed;
        }

        .stock-table td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Header Section */
        .header-container {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-left {
            width: 45%;
            display: inline-block;
            vertical-align: top;
        }

        .header-right {
            width: 53%;
            display: inline-block;
            vertical-align: top;
            text-align: right;
        }

        .logo-img {
            max-height: 40px;
            margin-bottom: 5px;
        }

        .company-url {
            display: block;
            margin-bottom: 15px;
            color: #334155;
            font-size: 9px;
            font-weight: 500;
        }

        .stats-container {
            font-size: 10px;
            line-height: 1.6;
        }

        .stats-label {
            display: inline-block;
            width: 85px;
            color: #334155;
        }

        .stats-value {
            font-weight: 800;
            color: #000;
        }

        .report-title {
            font-size: 32px;
            font-weight: 900;
            color: #1e293b;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .shop-address {
            margin-top: 5px;
            color: #334155;
            font-size: 10px;
            font-weight: 500;
        }

        .meta-row {
            margin-top: 10px;
            font-size: 11px;
            font-weight: 500;
        }

        .yellow-box {
            background-color: #facc15;
            padding: 2px 10px;
            border-radius: 10px;
            font-weight: 700;
            display: inline-block;
            margin-left: 5px;
            color: #000;
        }

        .phone-text {
            margin-top: 8px;
            font-weight: 500;
        }

        .thick-orange-line {
            width: 100%;
            height: 4px;
            background-color: #f97316;
            margin: 5px 0 15px 0;
        }

        /* Table Styling */
        .stock-table thead th {
            background-color: #e2e8f0;
            color: #475569;
            padding: 10px 8px;
            font-weight: 700;
            text-align: left;
            font-size: 10px;
            border: none;
        }

        .stock-table td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .sl-column { font-weight: 500; color: #4b5563; }
        .product-name-column { font-weight: 600; color: #1e293b; }

        /* Serial Numbers */
        /* Most serial numbers are short enough to fit one line at this
           column width, but some are much longer alphanumeric codes.
           white-space: nowrap alone let those bleed straight over the
           Total/Sale/In Stock columns instead of wrapping — word-break
           forces them to wrap within their own column when they must,
           which is a smaller readability hit than overlapping data. */
        .serial-number-text {
            color: #000;
            font-weight: bold;
            font-size: 9px;
            line-height: 1.4;
            width: 100%;
            word-break: break-all;
            overflow-wrap: break-word;
        }

        /* Short, single-value columns (brand, category, date, invoice,
           warehouse) read better slightly overflowing their column than
           breaking mid-word — e.g. "Apple" wrapping into "Appl"/"e". */
        .nowrap-cell {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 9px;
            width: 100%;
        }

        /* Quantity Boxes */
        .qty-box {
            display: inline-block;
            min-width: 32px;
            height: 24px;
            line-height: 24px;
            padding: 0 4px;
            text-align: center;
            border: 1px solid #94a3b8;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
            background-color: #ffffff;
            color: #334155;
        }

        /* Group Summary Row */
        .group-summary {
            background-color: #f1f5f9;
            border-radius: 8px;
        }

        .group-summary td {
            padding: 8px 15px;
            border-bottom: none;
            font-weight: 700;
            color: #1e293b;
            font-size: 11px;
        }

        .summary-stats-right {
            text-align: right;
            font-weight: 700;
        }

        .summary-stats-right span {
            margin-left: 15px;
        }

        .footer-info {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            color: #94a3b8;
            font-size: 8px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header-container">
        <div class="header-left">
             @if(!empty($tenantSettings['company_logo']))
                <img src="{{ $tenantSettings['company_logo'] }}" style="max-height: 40px; margin-bottom: 5px;"
                    alt="Logo">
                <br>
            @endif

            @if(!empty($tenantSettings['company_website']))
                <span class="company-url">{{ $tenantSettings['company_website'] }}</span>
            @endif

            <div class="stats-container">
                <div><span class="stats-label">Total Quantity</span> : <span class="stats-value">{{ $total_quantity }} Units</span></div>
                <div><span class="stats-label">Total Sale</span> : <span class="stats-value">{{ $total_sale }} Units</span></div>
                <div><span class="stats-label">Total In Stock</span> : <span class="stats-value">{{ $total_in_stock }} Units</span></div>
            </div>
        </div>

        <div class="header-right">
            <h1 class="report-title">STOCK REPORT</h1>
            <div class="shop-address">
                Shop: {{ $shop_info->name ?? '-' }} Level: 4 Block: A, Jamuna Future Park, Dhaka, Bangladesh
            </div>

            <div class="meta-row">
                Date : <span class="yellow-box">{{ $date }} &nbsp; {{ $time }}</span>
            </div>

            <div class="phone-text">
                Phone : +880 1897-715226
            </div>
        </div>
    </div>

    <div class="thick-orange-line"></div>

    @php
        // total/sale/in_stock are always the last 3 visible columns (they're
        // mandatory), so the summary row's label just spans everything else.
        $labelColspan = count($columns) - 3;
    @endphp

    <table class="stock-table">
        <thead>
            <tr>
                @foreach($columns as $key => $col)
                    <th width="{{ $col['width'] }}%" @if($col['align'] ?? null) class="text-center" @endif>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @php
                $currentProduct = null;
                $currentProductName = '';
                $pTotal = 0;
                $pSale = 0;
                $pStock = 0;
            @endphp

            @foreach ($stocks as $stock)

                @if ($currentProduct !== null && $currentProduct !== $stock['product_id'])
                    <tr class="group-summary">
                        <td colspan="{{ $labelColspan }}">{{ $currentProductName }}</td>
                        <td colspan="3" class="summary-stats-right">
                            <span>total: {{ $pTotal }}</span>
                            <span>Sale: {{ $pSale }}</span>
                            <span>In Stock: {{ str_pad($pStock, 2, '0', STR_PAD_LEFT) }}</span>
                        </td>
                    </tr>
                    @php $pTotal = 0; $pSale = 0; $pStock = 0; @endphp
                    {{-- Small spacer row --}}
                    <tr><td colspan="{{ count($columns) }}" style="padding: 2px; border: none;"></td></tr>
                @endif

                <tr>
                    @foreach($columns as $key => $col)
                        <td @if($col['align'] ?? null) class="text-center" @endif>
                            @switch($key)
                                @case('sl')
                                    <span class="sl-column">{{ $stock['sl'] }}</span>
                                    @break
                                @case('product_name')
                                    <span class="product-name-column">{{ $stock['product_name'] }}</span>
                                    @if($stock['attribute_values'])
                                        <div style="font-size: 8px; color: #94a3b8; font-weight: 400;">{{ $stock['attribute_values'] }}</div>
                                    @endif
                                    @break
                                @case('brand')
                                    <div class="nowrap-cell">{{ $stock['brand'] }}</div>
                                    @break
                                @case('category')
                                    <div class="nowrap-cell">{{ $stock['category'] }}</div>
                                    @break
                                @case('purchase_date')
                                    <div class="nowrap-cell">{{ $stock['purchase_date'] ? \Carbon\Carbon::parse($stock['purchase_date'])->format('d-m-Y') : '-' }}</div>
                                    @break
                                @case('supplier_name')
                                    {{ $stock['supplier_name'] }}
                                    @break
                                @case('full_invoice_number')
                                    <div class="nowrap-cell">{{ $stock['full_invoice_number'] ?? '-' }}</div>
                                    @break
                                @case('warehouse_name')
                                    <div class="nowrap-cell">{{ $stock['warehouse_name'] }}</div>
                                    @break
                                @case('serial_numbers')
                                    <div class="serial-number-text">
                                        @foreach($stock['serial_numbers'] as $sn)
                                            <div>{{ $sn }}</div>
                                        @endforeach
                                    </div>
                                    @break
                                @case('total')
                                    <span class="qty-box">{{ $stock['total'] }}</span>
                                    @break
                                @case('sale')
                                    <span class="qty-box">{{ $stock['sale'] }}</span>
                                    @break
                                @case('in_stock')
                                    <span class="qty-box">{{ $stock['in_stock'] }}</span>
                                    @break
                            @endswitch
                        </td>
                    @endforeach
                </tr>

                @php
                    $currentProduct = $stock['product_id'];
                    $currentProductName = $stock['product_name'];
                    $pTotal += $stock['total'];
                    $pSale += $stock['sale'];
                    $pStock += $stock['in_stock'];
                @endphp
            @endforeach

            {{-- Last Product Summary --}}
            @if(count($stocks) > 0)
                <tr class="group-summary">
                    <td colspan="{{ $labelColspan }}">{{ $currentProductName }}</td>
                    <td colspan="3" class="summary-stats-right">
                        <span>total: {{ $pTotal }}</span>
                        <span>Sale: {{ $pSale }}</span>
                        <span>In Stock: {{ str_pad($pStock, 2, '0', STR_PAD_LEFT) }}</span>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer-info">
        Generated by Salamat Innovation POS System &bull; {{ date('Y-m-d H:i:s') }}
    </div>
</body>

</html>
