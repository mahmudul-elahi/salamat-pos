<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>Order Invoice</title>

    <style>
        @font-face {
            font-family: 'Exo 2';
            font-weight: 200;
            font-style: normal;
            src: url("{{ storage_path('fonts/Exo2-ExtraLight.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Exo 2';
            font-weight: 400;
            font-style: normal;
            src: url("{{ storage_path('fonts/Exo2-Regular.ttf') }}") format('truetype');
        }


        @font-face {
            font-family: 'Exo 2';
            font-weight: 600;
            font-style: normal;
            src: url("{{ storage_path('fonts/Exo2-SemiBold.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Exo 2';
            font-weight: 700;
            font-style: normal;
            src: url("{{ storage_path('fonts/Exo2-Bold.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'DejaVu Sans';
            font-weight: 400;
            font-style: normal;
            src: url("{{ storage_path('fonts/DejaVuSans.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Li-Ador-Noirrit';
            font-weight: 300;
            font-style: normal;
            src: url("{{ storage_path('fonts/Li-Ador-Noirrit-ExtraLight.ttf') }}") format('truetype');
        }

        @page {
            margin: 25mm 0;
        }

        .bengali-address {
            font-family: 'Li-Ador-Noirrit', sans-serif;
            font-weight: 300;
            color: #181818;
        }

        body {
            font-family: 'Exo 2', sans-serif;
            font-weight: 200;
            margin: 0;
            padding: 0;
        }

        .invoice-container {
            font-size: 13px;
            width: 100%;
            margin: 0;
        }

        h1,
        h2 {
            color: #181818;
        }

        table {
            width: 100%;
            text-align: left;
            border-collapse: collapse;
            padding: 0 40px;
            line-height: inherit;
        }

        table th {
            padding: 2px 0 5px 0;
            /* vertical-align: top; */
        }

        table td {
            padding: 12px 5px;
        }

        /* Styling for the table of items */
        .items-table {
            margin-bottom: 10px;
        }

        .items-table-head {
            background-color: #f0f0f0;
        }

        .items-table thead th {
            font-weight: 400;
        }

        .items-table thead th:first-child {
            border-top-left-radius: 100%;
            border-bottom-left-radius: 100%;
        }

        .items-table thead th:last-child {
            border-top-right-radius: 100%;
            border-bottom-right-radius: 100%;
        }

        .items-table tbody td {
            border-bottom: 1px solid #a3a3a3;
            padding: 5px 0 10px 8px;
        }

        .table-footer {
            font-weight: 400;
        }

        .table-footer td {}

        .tk {
            max-width: 12px;
            width: 9px;
            margin-right: 3.5px;
        }

        .due-amount-section {
            page-break-inside: avoid;
        }

        .warranty-section {
            border: #04A98D 2px solid;
            border-radius: 20px;
            margin: 20px 40px;
            padding: 10px 20px 10px 0px;
            font-size: 11px;
        }

        .items-table tbody td {
            border-bottom: none !important;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <table style="margin-bottom: 10px;">
            <tr>

                <td style="width: 50%;">
                    @if($customer)
                    <p style="font-weight: 400">Billed To:</p>
                    <span style="font-weight: 400;">Name :</span> <span>{{ $customer->name }}</span><br />
                    <span style="font-weight: 400;">Phone :</span> <span>{{ $customer->phone }}</span><br />
                    <span style="font-weight: 400;">Email :</span> <span>{{ $customer->email }}</span><br />
                    @if (isset($customer->business_name))
                    <span style="font-weight: 400;">Business :</span>
                    <span>{{ $customer->business_name }}</span><br />
                    @endif

                    @if($order->shipping_address_id && $order->shippingAddress)
                    <span style="font-weight: 400;">Address :</span>
                    {{ $order->shippingAddress->getFullAddressAttribute() ?? '' }}
                    @else
                    <span style="font-weight: 400;">Address :</span>
                    {{ $order->address ?? '' }}
                    {{ $order->area?->name ?? '' }}
                    {{ $order->area?->district->name ?? '' }} {{ $order->area?->postcode ?? '' }}
                    @endif

                    @endif
                </td>

                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <h2 style="text-transform: uppercase; font-weight: 400; font-size:2rem; margin: 0;">
                        Invoice
                    </h2>
                    <span style="font-weight: 400;">Date:</span> {{ $order->created_at->format('Y-m-d') }}<br>
                    <span style="font-weight: 400;">Invoice No:</span> {{ $order->invoice_number }}
                </td>
            </tr>
        </table>

        <hr style="height:2px;border-width:0;color:gray;background-color:#b8b8b8a6;" />

        <table class="items-table" style="width:100%; border-collapse: collapse;">
            <thead class="items-table-head">
                <tr>
                    <th style="width:5%; text-align:center;">#</th>
                    <th style="width:35%;">Product</th>
                    <th style="width:15%; text-align:center;">Price</th>
                    <th style="width:15%; text-align:center;">Qty</th>
                    <th style="width:15%; text-align:center;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $key => $item)
                <tr>
                    <td style="text-align:center;">{{ $key+1 }}</td>
                    <td>
                        <p style="margin:0;">{{ Str::limit($item->product->product_name, 50) }}</p>
                        @if($item->variant && $item->variant->attributeValues)

                        @foreach ($item->variant->attributeValues as $attrValue)
                        <span
                            style="border:#414141 0.5px solid; display:inline-block; line-height:1; border-radius:100%; padding:1px 5px; font-size:10px; margin-right:2px;">
                            {{ $attrValue->value }}
                        </span>
                        @endforeach
                        @endif
                        @if ($item->product->warranty_day || $item->product->warranty_text)
                        @php
                        $wText = $item->product->warranty_text ?? '';
                        $wText = preg_replace('#<\ /?(p|div|h[1-6])[^>]*>#i', '', $wText);
                            $wText = trim(preg_replace('/\s+/', ' ', $wText));
                            @endphp

                            <p style="font-size:10px; margin:0; white-space: nowrap;">
                                Warranty Days: {{ $item->product->warranty_day ?? '' }}
                                @if ($wText)
                                (<span style="white-space: normal;">{!! $wText !!}</span>)
                                @endif
                            </p>
                            @endif


                    </td>

                    <td style="text-align:center;">{{ number_format($item->product_price, 2) }}</td>
                    <td style="text-align:center;">{{$item->quantity}}</td>
                    <td style="text-align:center;">
                        BDT {{number_format($item->product_price * $item->quantity, 2)}}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>


        <div style="page-break-inside: avoid;">
            <table style="width: 100%;">
                <tfoot class="table-footer">
                    <tr>
                        <td colspan="5" style="vertical-align: top; padding: 0 0 8px 8px;">
                            <p><strong>Note:</strong></p>
                            <p style="font-weight: 200; padding: 0; margin: 0;">{{ $order->note }}</p>
                        </td>

                        <!-- Totals section aligned right -->
                        <td style="width: 45%; vertical-align: top; padding-right: 40px;">
                            <table style="width: 100%; text-align: right; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 4px 0;">Subtotal :</td>
                                    <td style="padding: 4px 0; text-align: right;">
                                        BDT {{ number_format($order->sub_total, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0;">Total Discount :</td>
                                    <td style="padding: 4px 0; text-align: right;">
                                        BDT {{ number_format($totalDiscount, 2) }}

                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0;">Shipping Charge :</td>
                                    <td style="padding: 4px 0; text-align: right;">
                                        BDT {{ number_format($order->shipping_charge, 2) }}

                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0;">Grand Total :</td>
                                    <td style="padding: 4px 0; text-align: right;">
                                        BDT {{ number_format($order->grand_total, 2) }}

                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0;">Paid :</td>
                                    <td style="padding: 4px 0; text-align: right;">
                                        BDT {{ number_format($order->payable_amount, 2) }}

                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 0;">Due :</td>
                                    <td style="padding: 4px 0; text-align: right;">
                                        BDT {{ number_format($order->due_amount, 2) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="due-amount-section">
            <hr style="height:2px;border-width:0;color:gray;background-color:#b8b8b8a6;" />

            <div class="warranty-section">
                <ul>
                    <li>Please scan the QR code within 7 days of purchase to activate your warranty. After this period,
                        the product will no longer be eligible for warranty coverage.
                    </li>
                    <li>Before unboxing, carefully read and understand our Warranty Policy available at
                        <a href="https://salamatinnovation.com/policy/warranty-policy" style="text-decoration: none;">
                            https://salamatinnovation.com/policy/warranty-policy
                        </a>
                        Once unboxed, the
                        product cannot be returned
                        unless a valid warranty issue is confirmed.
                    </li>
                    <li>DDuring the warranty period, submit your warranty claim through our website and contact our
                        support number for assistance.
                    </li>
                </ul>
            </div>

            <div style="width: 100%; padding: 50px 0;">
                <center>Thank You For your Purchase!</center>
            </div>

            <table style="width: 100%; font-weight: 400;">
                <tr>
                    <td style="width: 30%; text-align: left;">
                        <p style="border-top: 1px solid #414141; padding-top: 5px; text-align: center; width: 200px;">
                            Customer Signature
                        </p>
                    </td>
                    <td style="width: 40%;">
                        <center>
                            <img src="{{ $logo }}" alt="Salamat Innovation Logo"
                                style="max-width: 80px; width: 62px;">
                            <p style="font-size:11px; font-weight:200; margin:0;">Powered by Salamat Innovation
                            </p>
                        </center>
                    </td>
                    <td style="width: 30%; text-align: right;">
                        <p
                            style="border-top: 1px solid #414141; padding-top: 5px; text-align: center; width: 200px; display: inline-block;">
                            Authority Signature
                        </p>
                    </td>
                </tr>
            </table>
        </div>

    </div>
</body>

</html>