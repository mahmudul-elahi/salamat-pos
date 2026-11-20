<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Purchase Invoice</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            width: 210mm;
            height: 297mm;
            font-family: 'exo2', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
            line-height: 1.4;
            padding-top: 55px;
        }

        .page {
            padding: 15mm;
        }

        /* Clearfix for Floats */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* ── HEADER ── */
        .header-container {
            width: 100%;
            margin-bottom: 30px;
        }

        .logo-area {
            float: left;
            width: 50%;
        }

        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #1eb2a6;
            letter-spacing: -1px;
        }

        .logo-text span {
            color: #333;
        }

        .website {
            color: #666;
            font-size: 11px;
            margin-top: -5px;
        }

        .contact-info {
            float: right;
            width: 50%;
            text-align: right;
        }

        /* ── BILLING & META ── */
        .meta-container {
            width: 100%;
            margin-bottom: 25px;
        }

        .bill-to {
            float: left;
            width: 60%;
        }

        .bill-to h3 {
            font-size: 13px;
            margin-bottom: 8px;
            color: #555;
        }

        .bill-table {
            border-collapse: collapse;
        }

        .bill-table td {
            padding: 2px 0;
            font-size: 12px;
            vertical-align: top;
        }

        .label-col {
            width: 65px;
            font-weight: 600;
        }

        .sep-col {
            width: 15px;
            text-align: center;
        }

        .invoice-info {
            float: right;
            width: 40%;
            text-align: right;
        }

        .invoice-info h1 {
            font-size: 30px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #000;
            text-transform: uppercase;
        }

        .info-row {
            font-size: 12px;
            margin-bottom: 3px;
        }

        .barcode-area {
            margin-top: 10px;
            text-align: right;
        }

        .barcode-img {
            height: 30px;
            width: 150px;
        }

        /* ── DIVIDER ── */
        .orange-divider {
            height: 3px;
            background-color: #f39233;


            width: 210mm;
            margin-left: -15mm;
            margin-bottom: 10px;
            clear: both;
            border-radius: 0;
            display: block;
        }

        /* ── ITEMS TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead th {
            background-color: #dce1e5;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #444;
        }

        .items-table tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .product-name {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .badge {
            display: inline-block;
            padding: 1px 8px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 10px;
            color: #555;
            margin-right: 5px;
        }

        .warranty {
            font-size: 10px;
            color: #777;
            margin-top: 5px;
        }

        .product-img-box {
            float: left;
            width: 42px;
            height: 42px;
            /* background: #f0f0f0; */
            border-radius: 5px;
            margin-right: 10px;
        }

        /* ── SUMMARY SECTION ── */
        .summary-wrapper {
            background-color: #e5e7eb;
            border-radius: 15px;
            padding: 20px;
            width: 95%;
            box-sizing: border-box;
            /* This ensures padding doesn't add to the width */
            clear: both;
            display: block;
            margin: 0;
            /* Remove any potential margins */
        }

        .summary-left {
            float: left;
            width: 45%;
            /* Adjusted to prevent float wrapping */
        }

        .summary-right {
            float: right;
            width: 50%;
            text-align: right;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 0;
            font-size: 12px;
        }

        .total-mrp-row td {
            font-size: 15px;
            font-weight: 800;
        }

        .footer-msg {
            margin-top: 50px;
            text-align: center;
            font-size: 13px;
            font-style: italic;
            color: #444;
        }

        .taka-icon {
            height: auto;
            width: 9px;
            vertical-align: middle;
            margin-top: 3px;
            opacity: 0.8;
        }
    </style>
</head>

<body>

    <div class="page">
        <!-- Meta Section -->
        <div class="meta-container clearfix">

            <div class="bill-to">
                <h3>Bill To</h3>
                <table class="bill-table">
                    <tr>
                        <td class="label-col">Name</td>
                        <td class="sep-col">:</td>
                        <td>{{$contact->name ?? ''}}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Business</td>
                        <td class="sep-col">:</td>
                        <td>{{$contact->company_name ?? '-'}}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Phone</td>
                        <td class="sep-col">:</td>
                        <td>{{$contact->phone ?? ''}}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Email</td>
                        <td class="sep-col">:</td>
                        <td>{{$contact->email ?? '-'}}</td>
                    </tr>

                    <tr>
                        <td class="label-col">Address</td>
                        <td class="sep-col">:</td>
                        <td>{{$contact->getFullAddress() ?? ''}}</td>
                    </tr>
                </table>
            </div>

            <div class="invoice-info">
                <h1>INVOICE</h1>
                <div class="info-row"><b>Date :</b> {{ $purchase->date }}</div>
                <div class="info-row"><b>Tracking No :</b></div>
                <div class="barcode-area">
                    <img class="barcode-img" src="{{ $barcode }}" alt="barcode">
                    <div style="font-size: 10px; margin-top: 3px;">{{$purchase->full_invoice_number}}</div>
                </div>
            </div>
        </div>

        <div class="orange-divider"></div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Product</th>
                    <th style="width: 80px;">Serial Number</th>
                    <th style="width: 70px; text-align: center;">Quantity</th>
                    <th style="width: 80px;">Unit Price</th>
                    <th style="width: 90px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                $groupedItems = $purchase->items->groupBy('product_variant_id');
                $index = 1;
                @endphp

                @foreach ($groupedItems as $variantId => $items)
                @php
                $firstItem = $items->first();
                $totalQty = $items->sum('quantity');
                @endphp

                <tr>
                    {{-- Serial --}}
                    <td style="font-weight: bold;">{{ str_pad($index++, 2, '0', STR_PAD_LEFT) }}</td>

                    {{-- Product --}}
                    <td class="clearfix">
                        @if(!empty($firstItem->product->product_image))
                        <img src="{{ to_cdn_url($firstItem->product->product_image) }}" alt="{{ $firstItem->product->product_name }}" class="product-img-box">
                        @endif

                        <div style="float: left; width: 80%;">
                            <div class="product-name">
                                {{ $firstItem->product->product_name }}
                            </div>

                            {{-- Attributes --}}
                            @foreach($firstItem->productVariant->attributeValues ?? [] as $attrValue)
                            <span class="badge">{{ $attrValue->value }}</span>
                            @endforeach

                            {{-- Warranty --}}
                            @if ($firstItem->product->warranty_day)
                            <div class="warranty">
                                Warranty Days: {{ $firstItem->product->warranty_day }}
                            </div>
                            @endif
                        </div>
                    </td>

                    {{-- Serial Number --}}
                    <td>
                        @foreach ($items as $item)
                        <div>{{ $item->serial_number }}</div>
                        @endforeach
                    </td>

                    {{-- Quantity --}}
                    <td style="text-align: center;">
                        {{ $totalQty }}
                    </td>

                    {{-- MRP --}}
                    <td>
                        @foreach ($items as $item)
                        <div>{{ number_format($item->unit_price, 2) }}</div>
                        @endforeach
                    </td>


                    {{-- Unit Price --}}
                    <td>
                        @foreach ($items as $item)
                        <div>{{ number_format($item->unit_price, 0) }}</div>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary-wrapper clearfix"> <!-- Removed inline margin-right: 50px -->
            <div class="summary-left">
                <table class="summary-table">
                    <tr>
                        <td style="width: 150px; font-weight: 600;">Total Quantity</td>
                        <td>: {{$totalQuantity}} PCS</td>
                    </tr>
                </table>
            </div>
            <div class="summary-right">
                <table class="summary-table">
                    <tr class="total-mrp-row">
                        <td style="text-align: right; padding-right:50px; white-space: nowrap;">Total Balance (=) :</td>
                        <td><img src="{{ $taka }}" class="taka-icon"> {{ number_format($grand_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-right:50px; font-weight: 600;">Paid:</td>
                        <td style="white-space: nowrap;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($received_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-right:50px; font-weight: 600;">Due :</td>
                        <td style="white-space: nowrap;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($due_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

    </div>

</body>

</html>