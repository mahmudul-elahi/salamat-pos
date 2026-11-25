<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>TEKZO Order Invoice</title>
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
            background-color: #e5e7eb;
            border-radius: 15px;
            padding: 20px 20px 20px 30px;
            margin-top: 20px;
            font-size: 13px;
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
        <!-- Header Section -->
        <div class="header-container clearfix">
            <div class="logo-area">
                <img
                    src="{{$logo}}"
                    alt="Tekzo Logo"
                    style="height: 45px; width: auto; display: block;" />
                <div class="website">www.tekzo.com.bd</div>
            </div>
            <div class="contact-info">
                <div>Phone : +8801897-715222</div>
                <div>Mail : hello@tekzo.com.bd</div>
            </div>
        </div>

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
                        <td class="label-col">Phone</td>
                        <td class="sep-col">:</td>
                        <td>{{ $customer->phone }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Email</td>
                        <td class="sep-col">:</td>
                        <td>{{ $customer->email }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Address</td>
                        <td class="sep-col">:</td>
                        <td>{{ $order->delivery_address ?: '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="invoice-info">
                <h1>INVOICE</h1>
                <div class="info-row"><b>Date :</b> {{ $order->created_at->format('Y-m-d H:i') }}</div>
                <div class="info-row"><b>Tracking No :</b></div>
                <div class="barcode-area">
                    <img class="barcode-img" src="{{ $barcode }}" alt="barcode">
                    <div style="font-size: 10px; margin-top: 3px;">{{ $order->invoice_number }}</div>
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
                    <th style="width: 70px; text-align: center;">Quantity</th>
                    <th style="width: 90px;">MRP</th>
                    <th style="width: 80px;">Discount</th>
                    <th style="width: 90px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalQuantity = 0;
                $totalGiftQuantity = 0;
                @endphp
                @foreach ($items as $key => $item)
                @php
                $totalQuantity += $item->quantity;
                $totalGiftQuantity += $item->total_gift_quantity;
                @endphp
                <tr>
                    <td style="font-weight: bold;">{{ $key+1 }}</td>
                    <td class="clearfix">
                        @if(!empty($item->product->product_image))
                        <img src="{{ to_cdn_url($item->product->product_image) }}" alt="{{ $item->product->product_image }}" class="product-img-box">
                        @endif
                        <div style="float: left; width: 80%;">
                            <div class="product-name">{{ Str::limit($item->product->product_name, 50) }}</div>

                            @if($item->preOwned)

                            @if($item->preOwned->battery_health)
                            <span class="badge">{{ $item->preOwned->battery_health }} %</span>
                            @endif

                            @foreach ($item->preOwned->attribute as $preAttr)
                            <span class="badge">{{ $preAttr->attributeValue->value }}</span>
                            @endforeach
                            @else

                            @if($item->variant && $item->variant->attributeValues)
                            @foreach ($item->variant->attributeValues as $attrValue)
                            <span class="badge">{{ $attrValue->value }}</span>
                            @endforeach
                            @endif
                            @if ($item->product->warranty_day)
                            <div class="warranty">Warranty Days: {{$item->product->warranty_day}}</div>
                            @endif
                            @endif

                            @if ($item->productCare && $item->productCare->care)
                            <div class="warranty">Product Care: {{$item->productCare->care?->name}} ×{{ $item->quantity }} </div>
                            @endif

                            @if ($item->preOwnedCare && $item->preOwnedCare->care)
                            <div class="warranty">Product Care: {{$item->preOwnedCare->care?->name}} ×{{ $item->quantity }} </div>
                            @endif

                        </div>

                        @if($item->giftProductVariant && $item->giftProductVariant->product)
                        <div style="clear: both; margin-top: 10px;">
                            <img src="{{ to_cdn_url($item->giftProductVariant->product->product_image) }}" class="product-img-box">
                            <div style="float: left;">
                                <div class="product-name">Gift Item x {{ $item->quantity }}</div>
                                <div class="product-name">
                                    {{ Str::limit($item->giftProductVariant->product->product_name, 40) }}
                                </div>

                                @if($item->giftProductVariant->productVariant->attributeValues)
                                <div>
                                    @foreach($item->giftProductVariant->productVariant->attributeValues as $giftAttr)
                                    <span class="badge">{{ $giftAttr->value }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                    </td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td>{{ number_format($item->product_price, 2) }}</td>
                    <td>{{ number_format($item->discount_amount * $item->quantity, 2) }}</td>
                    <td style="font-weight: bold;">{{ number_format((($item->product_price * $item->quantity) - ($item->discount_amount * $item->quantity)), 2) }}</td>
                </tr>
                @endforeach

            </tbody>
        </table>

        <!-- Summary Section -->
        <!-- Summary Section -->
        <div class="summary-wrapper clearfix">
            <div class="summary-left">
                <table class="summary-table">
                    <tr>
                        <td style="width: 150px; font-weight: 600;">Total Quantity</td>
                        <td>: {{ $totalQuantity }} PCS</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Payment Method &<br>(Partial Payment)</td>
                        <td>: <img src="{{ $taka }}" class="taka-icon"> {{ number_format($order->payable_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Additional Charge (+)</td>
                        <td>: <img src="{{ $taka }}" class="taka-icon">{{number_format($totalCareCharge, 2)}}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Shipping Charge (+)</td>
                        <td>: <img src="{{ $taka }}" class="taka-icon"> {{ number_format($order->shipping_charge, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Order Tax (0.00%) (+)</td>
                        <td>: <img src="{{ $taka }}" class="taka-icon"> 0.00</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Point (-)</td>
                        <td>: {{$totalPurchasePoint}}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;">Gift Item (-)</td>
                        <td>: {{$totalGiftQuantity}} PCS</td>
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
                        <td style="text-align: left; font-weight: 600;">Coupon Amount :</td>
                        <td style="font-weight: 600;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($order->discount_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Total Discount (-) :</td>
                        <td style="font-weight: 600;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($totalDiscount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Total Balance (=) :</td>
                        <td style="font-weight: 600;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($order->grand_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold; padding-top: 10px;">Paid :</td>
                        <td style="font-weight: bold; padding-top: 10px;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($order->payable_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: bold;">Due :</td>
                        <td style="font-weight: bold;"><img src="{{ $taka }}" class="taka-icon"> {{ number_format($order->grand_total - $order->payable_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer-msg">
            <ul>
                <li><b>Shipping Policy</b>: Any increase in shipping costs due to order weight increasing by 1 kg or more must be fully covered by the customer.</li>
                <li><b>Payment Policy</b>: Orders with an advance payment will be shipped via Cash on Delivery (COD), and the customer must pay the remaining balance upon receiving the product.</li>
                <li><b>Warranty Policy</b>: TEKZO will not cover any product warranty unless it is protected under the "TEKZO Care Protection" plan.</li>
            </ul>
        </div>
    </div>

</body>

</html>