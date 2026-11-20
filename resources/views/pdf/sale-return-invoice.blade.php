<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>Sale Return Invoice</title>

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
                    <p style="font-weight: 400">Billed To:</p>
                    <span style="font-weight: 400;">Name :</span> <span>{{ $customer->name }}</span><br />
                    <span style="font-weight: 400;">Phone :</span> <span>{{ $customer->phone }}</span><br />
                    <span style="font-weight: 400;">Email :</span> <span>{{ $customer->email }}</span><br />
                    @if (isset($customer->business_name))
                        <span style="font-weight: 400;">Business :</span>
                        <span>{{ $customer->business_name }}</span><br />
                    @endif
                    @if (isset($customer->address))
                        <span style="font-weight: 400;">Address :</span>
                        {{ $customer->full_address ?? '' }}
                        {{-- {{ $customer->area?->thana ?? '' }}
                    {{ $customer->area?->newDistrict->name ?? '' }} {{ $customer->area?->postcode ?? '' }} --}}
                    @endif
                </td>
                <td style="width: 50%; text-align: right; vertical-align: top;">
                    <h2 style="text-transform: uppercase; font-weight: 400; font-size:2rem; margin: 0;">
                        Invoice
                    </h2>
                    <span style="font-weight: 400;">Date:</span> {{ $saleReturn->created_at->format('Y-m-d') }}<br>
                    <span style="font-weight: 400;">Invoice No:</span> {{ $saleReturn->full_invoice_number }}
                </td>
            </tr>
        </table>

        <hr style="height:2px;border-width:0;color:gray;background-color:#b8b8b8a6;" />

        {{-- <table class="items-table" style="width: 100%; border-collapse: collapse;">
            <thead class="items-table-head">
                <tr>
                    <th style="width: 5%; text-align: center;">#</th>
                    <th style="width: 35%;">Product</th>
                    <th style="width: 15%;">Serial Number</th>
                    <th style="width: 15%; text-align: center;">Price</th>
                    <th style="width: 10%; text-align: center;">Discount</th>
                    <th style="width: 15%; text-align: center;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $groupedItems = $items->groupBy('product_id');
                @endphp

                @foreach ($groupedItems as $key => $productItems)
                    @php
                        $product = $productItems->first()->product;
                        $totalQuantity = $productItems->sum('quantity');
                        $totalUnitPrice = $productItems->sum(fn($item) => $item->unit_price * $item->quantity);
                        $totalDiscount = $productItems->sum(function ($item) {
                            // Check if discount type is percent or fixed
                            if ($item->discount_type === 'percent') {
                                return (($item->unit_price * $item->discount_amount) / 100) * $item->quantity;
                            } else {
                                // fixed
                                return $item->discount_amount * $item->quantity;
                            }
                        });
                        $firstVariant = $productItems->first()->productVariant;
                        $uniqueAttributes = $firstVariant->attributeValues
                            ->sortBy(fn($attr) => $attr->attribute->order ?? $attr->attribute->name)
                            ->pluck('value')
                            ->unique();
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <p style="margin: 0;">{{ Str::limit($product->product_name, 50) }}</p>
                            @foreach ($uniqueAttributes as $attrValue)
                                <span
                                    style="border: #414141 0.5px solid; display: inline-block; line-height: 1; border-radius: 100%; padding: 1px 5px; font-size: 10px; margin-right:2px;">
                                    {{ $attrValue }}
                                </span>
                            @endforeach
                        </td>
                        <td style="text-align: center;">
                            @foreach ($productItems as $item)
                                <div style="padding: 4px 0;">
                                    {{ $item->serial_number }}
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: center;">
                            @foreach ($productItems as $item)
                                <div style="padding: 4px 0;">
                                    {{ number_format($item->unit_price, 2) }}
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: center;">
                            @foreach ($productItems as $item)
                                @php
                                    $discountAmount =
                                        $item->discount_type === 'percent'
                                            ? ($item->unit_price * $item->discount_amount) / 100
                                            : $item->discount_amount;
                                @endphp
                                <div style="padding: 4px 0;">
                                    {{ number_format($discountAmount, 2) }}
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: center;">
                            @foreach ($productItems as $item)
                                @php
                                    $discountAmount =
                                        $item->discount_type === 'percent'
                                            ? ($item->unit_price * $item->discount_amount) / 100
                                            : $item->discount_amount;

                                    $lineTotal = ($item->unit_price - $discountAmount) * $item->quantity;
                                @endphp
                                <div style="padding: 4px 0;">
                                    BDT
                                    {{ number_format($lineTotal, 2) }}
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table> --}}
        <table class="items-table" style="width:100%; border-collapse: collapse;">
            <thead class="items-table-head">
                <tr>
                    <th style="width:5%; text-align:center;">#</th>
                    <th style="width:35%;">Product</th>
                    <th style="width:15%;">Serial Number</th>
                    <th style="width:15%; text-align:center;">Price</th>
                    <th style="width:15%; text-align:center;">Discount</th>
                    <th style="width:15%; text-align:center;">Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $groupedItems = $items->groupBy('product_variant_id');
                    $productIndex = 1;
                @endphp

                @foreach ($groupedItems as $key => $productItems)
                    @php
                        $product = $productItems->first()->product;
                        $firstVariant = $productItems->first()->productVariant;
                        $uniqueAttributes = $firstVariant?->attributeValues
                            ->sortBy(fn($attr) => $attr->attribute->order ?? $attr->attribute->name)
                            ->pluck('value')
                            ->unique();
                    @endphp

                    @foreach ($productItems as $i => $item)
                        <tr>
                            @if ($i === 0)
                                <td style="text-align:center;">{{ $productIndex }}</td>
                                <td>
                                    <p style="margin:0;">{{ Str::limit($product->product_name, 50) }}</p>
                                    @if ($uniqueAttributes)
                                    @foreach ($uniqueAttributes as $attrValue)
                                        <span
                                            style="border:#414141 0.5px solid; display:inline-block; line-height:1; border-radius:100%; padding:1px 5px; font-size:10px; margin-right:2px;">
                                            {{ $attrValue }}
                                        </span>
                                    @endforeach
                                    @endif
                                </td>
                            @else
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            @endif

                            <td style="text-align:center;">{{ $item->serial_number }}</td>
                            <td style="text-align:center;">
                                {{ number_format($item->unit_price, 2) }}
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
        <div style="page-break-inside: avoid;">
            <table style="width: 100%;">
                @php
                    $calculatedSubtotal = $items->sum(function ($item) {
                        return $item->unit_price * $item->quantity;
                    });
                @endphp
                <tfoot class="table-footer">
                    <tr>
                        <td colspan="4" rowspan="7" style="padding: 0 0 8px 8px; vertical-align: top;">
                            Note:
                            <p style="font-weight: 200; padding: 0;">
                                {{ $saleReturn->note ?? 'This is a sample note for this invoice' }}</p>
                        </td>
                        <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Subtotal :</td>
                        <td style="padding: 0 0 8px 8px; text-align: left;">
                            BDT {{ number_format($calculatedSubtotal, 2) }}
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
                            BDT {{ number_format($saleReturn->shipping_cost, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Order Tax
                            ({{ $saleReturn->order_tax ?? 0 }}%) :</td>
                        <td style="padding: 0 0 8px 8px; text-align: left;">
                            BDT
                            @php
                                $taxableAmount = $saleReturn->sub_total;
                                $orderTaxAmount = ($taxableAmount * ($saleReturn->order_tax ?? 0)) / 100;
                            @endphp
                            {{ number_format($orderTaxAmount, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Grand Total :</td>
                        <td style="padding: 0 0 8px 8px; text-align: left;">
                            BDT {{ number_format($saleReturn->grand_total, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Paid :</td>
                        <td style="padding: 0 0 8px 8px; text-align: left;">
                            BDT {{ number_format($return_amount, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 0 0 8px 0; text-align: right;">Due :</td>
                        <td style="padding: 0 0 8px 8px; text-align: left;">
                            BDT {{ number_format($due_amount, 2) }}
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
