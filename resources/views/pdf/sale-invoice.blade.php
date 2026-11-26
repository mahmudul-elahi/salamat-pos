<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sale Invoice</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 30mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            /* width: 210mm;
            height: 297mm;
            font-family: 'exo2', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
            line-height: 1.4;
            padding-top: 55px; */

            width: 210mm;
            font-family: 'exo2', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            background: #fff;
            line-height: 1.4;
            padding-top: 55px;
        }

        .main-page {
            padding: 15mm;
            page-break-inside: avoid;
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

            /* Forces the element to break out of the parent padding */
            width: 210mm;
            margin-left: -15mm;
            /* Matches the parent .page padding exactly */

            margin-bottom: 10px;
            clear: both;
            border-radius: 0;
            /* Full-width lines usually look better without radius */

            /* Ensures it prints correctly in all PDF engines */
            display: block;
            -webkit-print-color-adjust: exact;
        }

        /* ── ITEMS TABLE ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead {
            border-radius: 10px;
            /* Large radius to create pill-shaped header */
            overflow: hidden;
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
        }

        .summary-left {
            float: left;
            width: 65%;
        }

        .summary-right {
            float: right;
            width: 30%;
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
    <div class="main-page">
        <!-- Meta Section -->
        <div class="meta-container clearfix">
            <div class="bill-to">
                <h3>Bill To</h3>
                <table class="bill-table">
                    <tr>
                        <td class="label-col">Name</td>
                        <td class="sep-col">:</td>
                        <td>{{ $customer->name }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Business</td>
                        <td class="sep-col">:</td>
                        <td>{{ $customer->company_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Phone</td>
                        <td class="sep-col">:</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Email</td>
                        <td class="sep-col">:</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                    </tr>
                    @php($invoiceAddress = filled($sale->address) ? $sale->address : $customer?->invoice_address)
                    @if(filled($invoiceAddress))
                    <tr>
                        <td class="label-col">Address</td>
                        <td class="sep-col">:</td>
                        <td>{{ $invoiceAddress }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            <div class="invoice-info">
                <h1>INVOICE</h1>
                <div class="info-row"><b>Date :</b> {{ $sale->date }}</div>
                <div class="info-row"><b>Tracking No :</b></div>
                <div class="barcode-area">
                    <img class="barcode-img" src="{{ $barcode }}" alt="barcode">
                    <div style="font-size: 10px; margin-top: 3px;">{{ $sale->full_invoice_number }}</div>
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
                    <th style="width: 70px; text-align: center;">Serial Number</th>
                    <th style="width: 30px; text-align: center;">Quantity</th>
                    <th style="width: 60px;">MRP</th>
                    <th style="width: 60px;">Discount</th>
                    <th style="width: 80px;">Unit Price</th>
                </tr>
            </thead>
            <tbody>
                @php
                $groupedItems = $sale->items->groupBy('product_variant_id');
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

                        @if(count($groupedItems) < 10)
                            @if(!empty($firstItem->product?->product_image))
                            <img src="{{ to_cdn_url($firstItem->product->product_image) }}" class="product-img-box">
                            @endif
                            @endif


                            <div style="float: left; width: 80%;">
                                <div class="product-name">
                                    {{ $firstItem->product?->product_name }}
                                </div>

                                {{-- Attributes --}}
                                @if($firstItem?->productVariant && $firstItem?->productVariant?->attributeValues)
                                @foreach($firstItem->productVariant->attributeValues ?? [] as $attrValue)
                                <span class="badge">{{ $attrValue->value }}</span>
                                @endforeach
                                @endif

                                {{-- Warranty --}}
                                @if ($firstItem->product?->warranty_day)
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

                    {{-- Discount --}}
                    <td>
                        @foreach ($items as $item)
                        <div>{{ number_format($item->sale_type === 'exchange' ? 0 : ($item->discount_amount ?? 0), 2) }}</div>
                        @endforeach
                    </td>


                    {{-- Unit Price --}}
                    <td>
                        @foreach ($items as $item)
                        @php
                        $lineTotal = ($item->unit_price * $item->quantity) - ($item->discount_amount ?? 0);
                        $isExchange = $item->sale_type === 'exchange';
                        if ($isExchange) $lineTotal = -$lineTotal;
                        @endphp
                        <div style="{{ $isExchange ? 'color: red;' : '' }}">
                            {{ number_format($lineTotal, 0) }}
                        </div>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>


        <!-- Summary Section -->
        <div class="summary-wrapper clearfix">
            <div class="summary-left">
                <table class="summary-table">
                    <tr>
                        <td style="width: 150px; font-weight: 600;">Total Quantity</td>
                        <td>: {{ $totalQuantity }} PCS</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Payment Method &<br>({{ ucfirst($sale->status)}})</td>

                        @if(count($payment_summary) > 0)
                        @foreach ($payment_summary as $payment)
                        <td>: <img src="{{ $taka }}" class="taka-icon">
                            {{ number_format($payment['amount'], 2) }} ({{$payment['method']}})
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <td>: <img src="{{ $taka }}" class="taka-icon"> 0.00</td>
                    @endif

                    </tr>


                    <tr>
                        <td style="font-weight: 600;">Additional Charge @if($additional_charge_name)<br>({{ $additional_charge_name }})@endif</td>
                        <td>: <img src="{{ $taka }}" class="taka-icon"> {{ number_format($additional_charge, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Shipping Charge</td>
                        <td>: <img src="{{ $taka }}" class="taka-icon"> {{ number_format($shipping_cost, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Order Tax</td>
                        <td>: <img src="{{ $taka }}" class="taka-icon"> {{ number_format($order_tax, 2) }}</td>
                    </tr>

                </table>
            </div>
            <div class="summary-right">
                <table class="summary-table">
                    <tr class="total-mrp-row">
                        <td style="text-align: left;">Total MRP :</td>
                        <td><img src="{{ $taka }}" class="taka-icon"> {{ number_format($totalUnitPrice, 2) }}</td>

                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Total Discount (-) :</td>
                        <td><img src="{{ $taka }}" class="taka-icon"> {{ number_format($totalDiscount, 2) }}</td>

                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Total Balance (=) :</td>
                        <td style="font-weight: 600;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($sale->grand_total, 2) }}</td>

                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold; padding-top: 10px;">Paid :</td>
                        <td style="font-weight: 600;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($received_amount, 2) }}</td>

                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold;">Due :</td>
                        <td style="font-weight: 600;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($due_amount, 2) }}</td>

                    </tr>
                </table>
            </div>
        </div>

        <div class="footer-msg">
            Thank You For your Purchese!
        </div>
    </div>

</body>

</html>