<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sale Invoice</title>

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
            background-color: #DCDEE3;
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
            padding-bottom: 30px;
        }

        .table-footer td {
            padding: 0px;
        }

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
                <p style="font-weight: 400">Billed To:</p>
                <span style="font-weight: 400;">Name :</span> <span
                    style="font-weight: 400; color: #000;">{{ $customer->name }}</span><br />
                <span style="font-weight: 400;">Phone :</span> <span
                    style="font-weight: 400; color: #000;">{{ $customer->phone }}</span><br />
                <span style="font-weight: 400;">Email :</span> <span
                    style="font-weight: 400; color: #000;">{{ $customer->email }}</span><br />
                @if (isset($customer->business_name))
                    <span style="font-weight: 400;">Business :</span>
                    <span style="font-weight: 400; color: #000;">{{ $customer->business_name }}</span><br />
                @endif
                @if (isset($customer->address))
                    <span style="font-weight: 400;">Address :</span>
                    <span style="font-weight: 400; color: #000;">{{ $customer->full_address ?? '' }}</span>
                    {{-- {{ $customer->area?->thana ?? '' }}
            {{ $customer->area?->newDistrict->name ?? '' }} {{ $customer->area?->postcode ?? '' }} --}}
                @endif
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <h2 style="text-transform: uppercase; font-weight: 400; font-size:2rem; margin: 0;">
                    Invoice
                </h2>
                <span style="font-weight: 400;">Date:</span>
                <span style="font-weight: 400; color: #000;">{{ $sale->created_at->format('Y-m-d') }}</span><br>
                <span style="font-weight: 400;">Invoice No:</span>
                <img src="{{ $barcode }}" alt="barcode"
                     style="max-height: 80px; width: 80px; vertical-align: bottom;">
                <br>
                <span style="font-weight: 400; color: #000;">
                        {{ $sale->full_invoice_number }}
                    </span>
            </td>
        </tr>
    </table>
    <hr style="height:3px;border-width:0;color:gray;background-color:#02A98E;" />

    <table class="items-table" style="width:100%; border-collapse: collapse;">
        <thead class="items-table-head">
        <tr>
            <th style="width:2%; text-align:center;">#</th>
            <th style="width:33%;">Product</th>
            <th style="width:15%;">Serial Number</th>
            <th style="width:3%;">Quantity</th>
            <th style="width:13%; text-align:center;">Price</th>
            <th style="width:12%; text-align:center;">Discount</th>
            <th style="width:22%; text-align:center;">Total</th>
        </tr>
        </thead>
        <tbody>
        @php
            $groupedItems = $items->groupBy('product_variant_id');
            $productIndex = 1;

            $invoiceTotalQty = $items->sum('quantity');

        @endphp

        @foreach ($groupedItems as $key => $productItems)
            @php
                $product = $productItems->first()->product;
                $firstVariant = $productItems?->first()?->productVariant;
                $uniqueAttributes = $firstVariant?->attributeValues
                    ->sortBy(fn($attr) => $attr->attribute->order ?? $attr->attribute->name)
                    ->pluck('value')
                    ->unique();
                $totalQty = $productItems->sum('quantity');
            @endphp

            @foreach ($productItems as $i => $item)
                <tr>
                    @if ($i === 0)
                        <td style="text-align:center; color:#000; font-weight: 400;">{{ $productIndex }}</td>
                        <td>
                            <table style="border-collapse:collapse; padding:0; margin:0; width: 100%">
                                <tr>
{{--                                    <td style="vertical-align:top; padding-right:8px; width:60px;">--}}
{{--                                        <img src="{{ $product->product_image }}" alt="Product Image"--}}
{{--                                             style="width:60px; max-width:60px;">--}}
{{--                                    </td>--}}

                                    <td style="vertical-align:top;">
                                        <p style="margin:0; font-size:12px;">
                                            {{ Str::limit($product->product_name, 50) }}
                                        </p>
                                        @if ($uniqueAttributes)
                                            @foreach ($uniqueAttributes as $attrValue)
                                                <span
                                                    style="
                                                            border:#414141 1px solid;
                                                            display:inline-block;
                                                            line-height:1;
                                                            border-radius:100%;
                                                            padding:1px 5px;
                                                            font-size:10px;
                                                            margin-right:2px;
                                                        ">
                                                            {{ $attrValue }}
                                                        </span>
                                            @endforeach
                                        @endif

                                        @if ($product->warranty_day || $product->warranty_text)
                                            @php
                                                $wText = $product->warranty_text ?? '';
                                                $wText = preg_replace(
                                                    '#<\/?(p|div|h[1-6])[^>]*>#i',
                                                    '',
                                                    $wText,
                                                );
                                                $wText = trim(preg_replace('/\s+/', ' ', $wText));
                                            @endphp

                                            <p style="font-size:10px; margin:2px 0 0 0;">
                                                Warranty Days: {{ $product->warranty_day ?? '' }}
                                                @if ($wText)
                                                    ({{ $wText }})
                                                @endif
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @else
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    @endif

                    <td style="text-align:center;">{{ $item->serial_number }}</td>

                    @if ($i === 0)
                        <td style="text-align:center; font-weight:400;">
                            {{ $totalQty }}
                        </td>
                    @else
                        <td>&nbsp;</td>
                    @endif
                    <td style="text-align:center;">
                        BDT {{ number_format($item->unit_price, 2) }}
                    </td>
                    <td style="text-align:center;">
                        @php
                            $discountAmount =
                                $item->discount_type === 'percent'
                                    ? ($item->unit_price * $item->discount_amount) / 100
                                    : $item->discount_amount;
                        @endphp
                        {{ number_format($discountAmount, 2) }}
                    </td>
                    <td style="text-align:center;">
                        @php
                            $lineTotal = ($item->unit_price - $discountAmount) * $item->quantity;
                        @endphp
                        BDT {{ number_format($lineTotal, 2) }}
                    </td>
                </tr>
            @endforeach
            @php $productIndex++; @endphp
        @endforeach
        </tbody>
    </table>


    {{-- <div style="page-break-inside: avoid;">
    <table style="width: 100%;">
        @php
            $calculatedSubtotal = $items->sum(function ($item) {
                return $item->unit_price * $item->quantity;
            });
        @endphp
        <tfoot class="table-footer">
            <tr>
                <td colspan="4" rowspan="8" style="padding: 0 0 8px 8px; vertical-align: top;">
                    Note:
                    <p style="font-weight: 200; padding: 0;">
                        {{ $sale->note ?? 'This is a sample note for this invoice' }}</p>
                </td>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Subtotal :</td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    {{ number_format($calculatedSubtotal, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Total Discount :</td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    @php
                        $totalDiscount = $items->sum(function ($item) {
                            if ($item->discount_type === 'percent') {
                                return (($item->unit_price * $item->discount_amount) / 100) * $item->quantity;
                            } else {
                                return $item->discount_amount * $item->quantity;
                            }
                        });
                    @endphp
                    {{ number_format($totalDiscount, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Shipping Charge :</td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    {{ number_format($sale->shipping_cost, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Order Tax
                    ({{ $sale->order_tax ?? 0 }}%) :
                </td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    @php
                        $taxableAmount = $sale->sub_total;
                        $orderTaxAmount = ($taxableAmount * ($sale->order_tax ?? 0)) / 100;
                    @endphp
                    {{ number_format($orderTaxAmount, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Additional Charge ({{ $sale->additional_charge_name ?? '' }}) :</td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    {{ number_format($sale->additional_charge ?? 0, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Grand Total :</td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    {{ number_format($sale->grand_total, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Paid :</td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    {{ number_format($received_amount, 2) }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Due :</td>
                <td style="padding: 0 0 8px 8px; text-align: left;">
                    BDT 
                    {{ number_format($due_amount, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div> --}}
    <div
        style="background-color: #DCDEE3; border-radius: 25px; margin-top: 10px; margin-left: 40px; margin-right: 40px;">
        @php
            $calculatedSubtotal = $items->sum(fn($item) => $item->unit_price * $item->quantity);
            $totalDiscount = $items->sum(function ($item) {
                return $item->discount_type === 'percent'
                    ? (($item->unit_price * $item->discount_amount) / 100) * $item->quantity
                    : $item->discount_amount * $item->quantity;
            });
            $orderTaxAmount = ($sale->sub_total * ($sale->order_tax ?? 0)) / 100;
        @endphp

        <table class="table-footer"
               style="width: 100%; border-collapse: collapse; table-layout: fixed; line-height: 1.8; padding: 0px; padding-bottom: 10px;">
            <tr>
                <td
                    style="width: {{ $payment_summary && count($payment_summary) ? '60%' : '50%' }}; vertical-align: top;">
                    <table
                        style="width: {{ $payment_summary && count($payment_summary) ? '100%' : '90%' }}; table-layout: fixed; border-collapse: collapse;">
                        @if (!empty($payment_summary) && count($payment_summary))
                            <tr>
                                <td style="text-align:left; padding:0; width:35%; vertical-align: top;">
                                    Payment Method & <br>
                                    (Partial Payment) :
                                </td>
                                <td style="text-align:left; padding:0; vertical-align: top; width:50%;">
                                    @foreach ($payment_summary as $payment)
                                        BDT {{ number_format($payment['amount'], 2) }}
                                        ({{ $payment['method'] }})
                                        @if (!$loop->last)
                                            ,
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td style="text-align:left; padding:0;">
                                Total Quantity :
                            </td>
                            <td style="text-align:left; padding:0; vertical-align: middle;">
                                {{$invoiceTotalQty}}-PCS
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align:left; padding:0;">
                                @if($sale->additional_charge_name)
                                    Additional Charge <br> ({{ $sale->additional_charge_name }}) :
                                @else
                                    Additional Charge :
                                @endif
                            </td>
                            <td style="text-align:left; padding:0; vertical-align: middle;">
                                BDT {{ number_format($sale->additional_charge ?? 0, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align:left; padding:0;">
                                Shipping Charge :
                            </td>
                            <td style="text-align:left; padding:0; vertical-align: middle;">
                                BDT {{ number_format($sale->shipping_cost ?? 0, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="width:40%; text-align:left; padding:0;">
                                Order Tax ({{ number_format($sale->order_tax ?? 0, 2) }}%) :
                            </td>
                            <td style="width:10%; text-align:left; padding:0; vertical-align: middle;">
                                BDT {{ number_format($orderTaxAmount, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <table style="width:100%; table-layout: fixed; border-collapse: collapse;">
                        <tr>
                            <td style="width:65%; text-align:right; padding:0;">
                                Subtotal :
                            </td>
                            <td style="width:35%; text-align:right; padding:0;">
                                BDT {{ number_format($calculatedSubtotal, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td style="text-align:right; padding:0;">
                                Total Discount :
                            </td>
                            <td style="text-align:right; padding:0;">
                                BDT {{ number_format($totalDiscount, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td style="text-align:right; padding:0; font-weight:normal;">
                                Grand Total :
                            </td>
                            <td style="text-align:right; padding:0; font-weight:normal;">
                                BDT {{ number_format($sale->grand_total, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td style="text-align:right; padding:0;">
                                Paid :
                            </td>
                            <td style="text-align:right; padding:0;">
                                BDT {{ number_format($received_amount, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td style="text-align:right; padding:0;">
                                Due :
                            </td>
                            <td style="text-align:right; padding:0;">
                                BDT {{ number_format($due_amount, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>

            </tr>
        </table>
    </div>

    <div class="due-amount-section">
        {{-- <hr style="height:2px;border-width:0;color:gray;background-color:#b8b8b8a6;" /> --}}
        {{-- <div class="warranty-section">
<ul>
    <li>Please scan the QR code within 7 days of purchase to activate your warranty. After this period,
        the product will no longer be eligible for warranty coverage.</li>
    <li>Before unboxing, carefully read and understand our Warranty Policy available at
        <a href="https://salamatinnovation.com/policy/warranty-policy" style="text-decoration: none;">
            https://salamatinnovation.com/policy/warranty-policy
        </a>
        Once unboxed, the
        product cannot be returned
        unless a valid warranty issue is confirmed.
    </li>
    <li>DDuring the warranty period, submit your warranty claim through our website and contact our
        support number for assistance.</li>
</ul>
</div> --}}

        <div style="width: 100%; padding: 50px 0;">
            <center>Thank You For your Purchase!</center>
        </div>

        {{--            <table style="width: 100%; font-weight: 400;"> --}}
        {{--                <tr> --}}
        {{--                    <td style="width: 30%; text-align: left;"> --}}
        {{--                        <p style="border-top: 1px solid #414141; padding-top: 5px; text-align: center; width: 200px;"> --}}
        {{--                            Customer Signature --}}
        {{--                        </p> --}}
        {{--                    </td> --}}
        {{--                    <td style="width: 40%;"> --}}
        {{--                        <center> --}}
        {{--                            <img src="{{ $logo }}" alt="Salamat Innovation Logo" --}}
        {{--                                style="max-width: 80px; width: 62px;"> --}}
        {{--                            <p style="font-size:11px; font-weight:200; margin:0;">Powered by Salamat Innovation --}}
        {{--                            </p> --}}
        {{--                        </center> --}}
        {{--                    </td> --}}
        {{--                    <td style="width: 30%; text-align: right;"> --}}
        {{--                        <p --}}
        {{--                            style="border-top: 1px solid #414141; padding-top: 5px; text-align: center; width: 200px; display: inline-block;"> --}}
        {{--                            Authority Signature --}}
        {{--                        </p> --}}
        {{--                    </td> --}}
        {{--                </tr> --}}
        {{--            </table> --}}

    </div>

</div>
</body>

</html>
