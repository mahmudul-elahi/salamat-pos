<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Deposit Statement</title>
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
            width: 85px;
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

        .statement-table thead th {
            background-color: #e2e8f0;
            color: #475569;
            padding: 10px 8px;
            font-weight: 700;
            text-align: left;
            font-size: 10px;
            border: none;
        }

        .statement-table td {
            padding: 10px 8px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .sl-column { font-weight: 500; color: #4b5563; }

        .summary-box {
            page-break-inside: auto;
            background-color: #dfe3e8;
            border-radius: 25px;
            margin-top: 30px;
            padding: 25px 35px;
            page-break-inside: auto;
        }

        .summary-box-inner {
            float: right;
            width: 280px;
        }

        .summary-grand-total {
            border-bottom: 2px solid #1e293b;
            display: block;
            width: 100%;
            padding-bottom: 3px;
            margin-bottom: 15px;
            color: #1e293b;
            text-align: left;
        }

        .grand-total-label {
            font-size: 15px;
            font-weight: 700;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            clear: both;
        }

        .summary-table td {
            padding: 4px 0;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            vertical-align: middle;
        }

        .summary-label {
            width: 140px;
            text-align: left;
        }

        .summary-value {
            text-align: left;
        }

        .summary-separator {
            width: 20px;
            text-align: center;
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
                <div><span class="stats-label">Grand Total</span> : <span
                        class="stats-value">BDT {{ number_format($total_deposit, 2) }}</span></div>
            </div>
        </div>

        <div class="header-right">
            <h1 class="report-title">DEPOSIT STATEMENT</h1>
            <div class="shop-address">
                <strong>{{ $shop_info->name ?? '' }}</strong><br>
                @if(!empty($tenantSettings['primary_address']))
                    {{ $tenantSettings['primary_address'] }}
                @elseif($shop_info && !empty($shop_info->address))
                    {{ $shop_info->address }}
                @else
                    -
                @endif
            </div>

            <div class="meta-row">
                Date : <span class="yellow-box">{{ $date }} &nbsp; {{ $time }}</span>
            </div>

            <div class="phone-text">
                Phone :
                @if(!empty($tenantSettings['primary_phone_number']))
                    {{ $tenantSettings['primary_phone_number'] }}
                @elseif($shop_info && !empty($shop_info->phone))
                    {{ $shop_info->phone }}
                @else
                    -
                @endif
            </div>
        </div>
    </div>

    <div class="thick-orange-line"></div>

    <table class="statement-table">
        <thead>
        <tr>
            <th width="10%">SL</th>
            <th width="15%">Date</th>
            <th width="20%">Title</th>
            @if(in_array('user', $columns)) <th width="15%">Director</th> @endif
            @if(in_array('narration', $columns)) <th width="15%">Narration</th> @endif
            @if(in_array('amount', $columns)) <th width="25%" class="text-right" style="text-align: right;">Amount</th> @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($deposits as $deposit)
            <tr>
                <td class="sl-column">{{ $deposit['sl'] }}</td>
                <td>{{ $deposit['date'] }}</td>
                <td>{{ $deposit['title'] }}</td>
                @if(in_array('user', $columns)) <td>{{ $deposit['user'] }}</td> @endif
                @if(in_array('narration', $columns)) <td>{{ $deposit['narration'] }}</td> @endif
                @if(in_array('amount', $columns)) <td class="text-right">{{ number_format($deposit['amount'], 2) }}</td> @endif
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-box-inner">
            <div class="summary-grand-total">
                <span class="grand-total-label">Grand Total- {{ number_format($total_deposit, 2) }}</span>
            </div>

            <table class="summary-table">
                <tr>
                    <td class="summary-label">Total Deposit</td>
                    <td class="summary-separator">:</td>
                    <td class="summary-value">
                        BDT {{ number_format($total_deposit, 2) }}
                    </td>
                </tr>

            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="footer-info">
        Generated by TekZo POS System &bull; {{ date('Y-m-d H:i:s') }}
    </div>
</body>

</html>
