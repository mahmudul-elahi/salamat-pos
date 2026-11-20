<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Director Profile</title>
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

        .report-title {
            font-size: 25px;
            font-weight: 900;
            color: #1e293b;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .meta-row {
            font-size: 11px;
            font-weight: 500;
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

        .info-box {
            background-color: #f8f9fb;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 15px;
        }

        .info-label {
            color: #94a3b8;
            font-size: 9px;
            text-transform: uppercase;
            display: block;
        }

        .info-value {
            font-weight: 700;
            color: #1e293b;
            font-size: 12px;
        }

        .stats-row {
            width: 100%;
            margin-bottom: 20px;
        }

        .stat-box {
            width: 32%;
            display: inline-block;
            vertical-align: top;
            background-color: #f8f9fb;
            border-radius: 8px;
            padding: 12px 16px;
            box-sizing: border-box;
        }

        .stat-box + .stat-box {
            margin-left: 2%;
        }

        .stat-label {
            font-size: 9px;
            color: #94a3b8;
            text-transform: uppercase;
            display: block;
            margin-bottom: 3px;
        }

        .stat-value {
            font-size: 16px;
            font-weight: 800;
            color: #1e293b;
        }

        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
            margin: 20px 0 8px 0;
            text-transform: uppercase;
        }

        .data-table thead th {
            background-color: #e2e8f0;
            color: #475569;
            padding: 8px 8px;
            font-weight: 700;
            text-align: left;
            font-size: 10px;
            border: none;
        }

        .data-table td {
            padding: 8px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .text-right {
            text-align: right;
        }

        .empty-row td {
            text-align: center;
            color: #94a3b8;
            padding: 12px 8px;
        }

        .footer-info {
            clear: both;
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
        @if(!empty($logo))
            <img src="{{ $logo }}" style="max-height: 40px; margin-bottom: 5px;" alt="Logo">
            <br>
        @endif
        <span class="info-value">{{ $companyName ?? '-' }}</span>
    </div>

    <div class="header-right">
        <h1 class="report-title">{{ $reportTitle ?? 'Director Profile' }}</h1>
        @if(!empty($shopName))
            <div class="meta-row">Shop : {{ $shopName }}</div>
        @endif
        <div class="meta-row">
            Date : <span class="yellow-box">{{ date('d/m/Y') }}</span>
        </div>
    </div>
</div>

<div class="thick-orange-line"></div>

<div class="info-box">
    <table>
        <tr>
            <td width="25%">
                <span class="info-label">Name</span>
                <span class="info-value">{{ $director->name }}</span>
            </td>
            <td width="25%">
                <span class="info-label">Title</span>
                <span class="info-value">{{ $director->title ?? '-' }}</span>
            </td>
            <td width="25%">
                <span class="info-label">Phone</span>
                <span class="info-value">{{ $director->phone ?? '-' }}</span>
            </td>
            <td width="25%">
                <span class="info-label">Email</span>
                <span class="info-value">{{ $director->email ?? '-' }}</span>
            </td>
        </tr>
    </table>
</div>

<div class="stats-row">
    <div class="stat-box">
        <span class="stat-label">Total Deposit</span>
        <span class="stat-value">BDT {{ number_format($totalDeposit, 2) }}</span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Total Withdraw</span>
        <span class="stat-value">BDT {{ number_format($totalWithdraw, 2) }}</span>
    </div>
    <div class="stat-box">
        <span class="stat-label">Total Balance</span>
        <span class="stat-value">BDT {{ number_format($totalBalance, 2) }}</span>
    </div>
</div>

<div class="section-title">Shop Balances</div>
<table class="data-table">
    <thead>
    <tr>
        <th width="40%">Shop Name</th>
        <th width="20%" class="text-right">Deposit</th>
        <th width="20%" class="text-right">Withdraw</th>
        <th width="20%" class="text-right">Balance</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($shopBalances as $shop)
        <tr>
            <td>{{ $shop['shop_name'] }}</td>
            <td class="text-right">BDT {{ number_format($shop['total_deposit'], 2) }}</td>
            <td class="text-right">BDT {{ number_format($shop['total_withdraw'], 2) }}</td>
            <td class="text-right">BDT {{ number_format($shop['balance'], 2) }}</td>
        </tr>
    @empty
        <tr class="empty-row">
            <td colspan="4">No shop data found</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="section-title">Deposit Transactions</div>
<table class="data-table">
    <thead>
    <tr>
        <th width="15%">Date</th>
        <th width="40%">Title</th>
        <th width="25%">Shop</th>
        <th width="20%" class="text-right">Amount</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($deposits as $deposit)
        <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($deposit->date)->format('d/m/Y') }}</td>
            <td>{{ $deposit->title }}</td>
            <td>{{ $deposit->tenant?->name ?? '-' }}</td>
            <td class="text-right">BDT {{ number_format($deposit->amount, 2) }}</td>
        </tr>
    @empty
        <tr class="empty-row">
            <td colspan="4">No deposit transactions found</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="section-title">Withdrawal Transactions</div>
<table class="data-table">
    <thead>
    <tr>
        <th width="15%">Date</th>
        <th width="40%">Title</th>
        <th width="25%">Shop</th>
        <th width="20%" class="text-right">Amount</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($withdrawals as $withdrawal)
        <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($withdrawal->date)->format('d/m/Y') }}</td>
            <td>{{ $withdrawal->title }}</td>
            <td>{{ $withdrawal->tenant?->name ?? '-' }}</td>
            <td class="text-right">BDT {{ number_format($withdrawal->amount, 2) }}</td>
        </tr>
    @empty
        <tr class="empty-row">
            <td colspan="4">No withdrawal transactions found</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="footer-info">
    Generated by {{ $companyName ?? '-' }} &bull; {{ date('Y-m-d H:i:s') }}
</div>
</body>

</html>
