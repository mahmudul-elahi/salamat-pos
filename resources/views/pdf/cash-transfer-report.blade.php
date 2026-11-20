<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Cash Transfer Report</title>
    <style>
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

        .shop-address {
            margin-top: 5px;
            color: #334155;
            font-size: 10px;
            font-weight: 500;
        }

        .meta-row {
            font-size: 11px;
            font-weight: 500;
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .yellow-box {
            background-color: #facc15;
            padding: 2px 10px;
            border-radius: 10px;
            font-weight: 700;
            margin-left: 5px;
            color: #000;
        }

        .thick-orange-line {
            width: 100%;
            height: 4px;
            background-color: #f97316;
            margin: 5px 0 15px 0;
        }

        .transfer-table thead th {
            background-color: #e2e8f0;
            color: #475569;
            padding: 10px 8px;
            font-weight: 700;
            text-align: left;
            font-size: 10px;
            border: none;
        }

        .transfer-table td {
            padding: 10px 8px;
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

        .reference-column {
            color: #9568ffff !important;
        }

        .status-badge {
            text-transform: capitalize;
            font-weight: 600;
        }

        .footer-info {
            clear: both;
            margin-top: 50px;
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
            <div><span class="stats-label">Total Transfers</span> : <span
                    class="stats-value">{{ $total_transfers ?? 0 }}</span></div>
            <div><span class="stats-label">Total Amount</span> : <span
                    class="stats-value">BDT {{ number_format($total_amount ?? 0, 2) }}</span></div>
        </div>
    </div>

    <div class="header-right">
        <h1 class="report-title">CASH TRANSFER REPORT</h1>
        <div class="shop-address">
            @if(!empty($tenantSettings['primary_address']))
                {{ $tenantSettings['primary_address'] }}
            @elseif(!empty($tenantSettings['secondary_address']))
                {{ $tenantSettings['secondary_address'] }}
            @else
                -
            @endif
        </div>

        <div class="meta-row">
            Date : <span class="yellow-box">{{ date('d.m.Y') }}</span>
        </div>
    </div>
</div>

<div class="thick-orange-line"></div>

<table class="transfer-table">
    <thead>
    <tr>
        <th width="13%">Reference No</th>
        <th width="10%">Date</th>
        <th width="16%">From Warehouse</th>
        <th width="16%">To Warehouse</th>
        <th width="10%" class="text-right">Amount</th>
        <th width="10%">Status</th>
        <th width="12%">Created By</th>
        <th width="13%">Received By</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($transfers as $transfer)
        <tr>
            <td class="reference-column">{{ $transfer['reference_no'] }}</td>
            <td>{{ $transfer['transfer_date'] }}</td>
            <td>{{ $transfer['from_warehouse_name'] }}</td>
            <td>{{ $transfer['to_warehouse_name'] }}</td>
            <td class="text-right">{{ number_format($transfer['amount'], 2) }}</td>
            <td class="status-badge">{{ $transfer['status'] }}</td>
            <td>{{ $transfer['created_by_name'] }}</td>
            <td>{{ $transfer['received_by_name'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer-info">
    Generated by {{$tenantSettings['company_name'] ?? ''}} POS System &bull; {{ date('Y-m-d H:i:s') }}
</div>
</body>

</html>
