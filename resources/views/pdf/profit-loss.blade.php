<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Profit Loss Report</title>
    <style>
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

        tr {
            page-break-inside: avoid;
        }

        table {
            page-break-inside: auto;
            width: 100%;
            border-collapse: collapse;
        }

        .pl-table {
            table-layout: fixed;
        }

        .pl-table td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .nowrap-cell {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 9px;
            width: 100%;
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

        .stats-container {
            font-size: 10px;
            line-height: 1.6;
        }

        .stats-label {
            display: inline-block;
            width: 100px;
            color: #334155;
        }

        .stats-value {
            font-weight: 800;
            color: #000;
        }

        .report-title {
            font-size: 25px;
            font-weight: 900;
            color: #1e293b;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .meta-row {
            margin-top: 10px;
            font-size: 11px;
            font-weight: 500;
            color: #475569;
        }

        .yellow-box {
            background-color: #facc15;
            padding: 3px 12px;
            border-radius: 9999px;
            font-weight: 800;
            display: inline-block;
            margin-left: 5px;
            color: #1e293b;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .thick-orange-line {
            width: 100%;
            height: 4px;
            background-color: #f97316;
            margin: 5px 0 15px 0;
        }

        /* Table Styling */
        .pl-table thead th {
            background-color: #e2e8f0;
            color: #475569;
            padding: 10px 8px;
            font-weight: 700;
            text-align: left;
            font-size: 10px;
            border: none;
        }

        .pl-table td {
            padding: 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .sl-column {
            font-weight: 500;
            color: #4b5563;
        }

        .product-name-column {
            font-weight: 600;
            color: #1e293b;
        }

        .profit-positive {
            color: #16a34a;
            font-weight: 700;
        }

        .profit-negative {
            color: #dc2626;
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 8px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .status-due {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-partial {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-paid {
            background-color: #dcfce7;
            color: #166534;
        }

        .grand-total-row td {
            background-color: #f1f5f9;
            border-bottom: none;
            border-top: 2px solid #94a3b8;
            font-weight: 800;
            color: #1e293b;
            font-size: 11px;
            padding: 10px 8px;
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
            <div class="stats-container">
                <div><span class="stats-label">Total Sale</span> : <span class="stats-value">{{ number_format($grand_total_sale, 2) }}</span></div>
                <div><span class="stats-label">Total Purchase</span> : <span class="stats-value">{{ number_format($grand_total_purchase, 2) }}</span></div>
                <div><span class="stats-label">Profit / Loss</span> : <span class="stats-value">{{ number_format($grand_profit_loss, 2) }}</span></div>
            </div>
        </div>

        <div class="header-right">
            <h2 class="report-title">PROFIT LOSS REPORT</h2>
            <div class="meta-row">
                Date : <span class="yellow-box">{{ $date }} &nbsp; {{ $time }}</span>
            </div>
        </div>
    </div>

    <div class="thick-orange-line"></div>

    <table class="pl-table">
        <thead>
            <tr>
                @foreach($columns as $key => $col)
                <th width="{{ $col['width'] }}%" @if($col['align'] ?? null) class="text-{{ $col['align'] }}" @endif>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            <tr>
                @foreach($columns as $key => $col)
                <td @if($col['align'] ?? null) class="text-{{ $col['align'] }}" @endif>
                    @switch($key)
                    @case('sl')
                    <span class="sl-column">{{ $row['sl'] }}</span>
                    @break
                    @case('date')
                    <div class="nowrap-cell">{{ $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d-m-Y') : '-' }}</div>
                    @break
                    @case('invoice_number')
                    <div class="nowrap-cell">{{ $row['invoice_number'] ?? '-' }}</div>
                    @break
                    @case('product')
                    <span class="product-name-column">{{ $row['product_name'] ?? '-' }}</span>
                    @if($row['product_variant'])
                    <div style="font-size: 8px; color: #94a3b8; font-weight: 400;">{{ $row['product_variant'] }}</div>
                    @endif
                    @break
                    @case('seller')
                    <div class="nowrap-cell">{{ $row['seller'] ?? '-' }}</div>
                    @break
                    @case('created_by')
                    <div class="nowrap-cell">{{ $row['created_by'] ?? '-' }}</div>
                    @break
                    @case('payment_method')
                    <div class="nowrap-cell">{{ $row['payment_method'] ?: '-' }}</div>
                    @break
                    @case('status')
                    @php
                    $statusClass = match($row['status']) {
                    'due' => 'status-due',
                    'partially_paid' => 'status-partial',
                    default => 'status-paid',
                    };
                    @endphp
                    @if($row['status'])
                    <span class="status-badge {{ $statusClass }}">{{ $row['status'] }}</span>
                    @else
                    -
                    @endif
                    @break
                    @case('total_sale_price')
                    {{ number_format($row['total_sale_price'], 2) }}
                    @break
                    @case('total_purchase_price')
                    {{ number_format($row['total_purchase_price'], 2) }}
                    @break
                    @case('profit_loss')
                    <span class="{{ $row['profit_loss'] >= 0 ? 'profit-positive' : 'profit-negative' }}">{{ number_format($row['profit_loss'], 2) }}</span>
                    @break
                    @endswitch
                </td>
                @endforeach
            </tr>
            @endforeach

            <tr class="grand-total-row">
                <td colspan="{{ count($columns) - 3 }}">Grand Total</td>
                <td class="text-right">{{ number_format($grand_total_sale, 2) }}</td>
                <td class="text-right">{{ number_format($grand_total_purchase, 2) }}</td>
                <td class="text-right">{{ number_format($grand_profit_loss, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer-info">
        Generated by Salamat Innovation POS System &bull; {{ date('Y-m-d H:i:s') }}
    </div>
</body>

</html>
