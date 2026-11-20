<!DOCTYPE html>
<html>

<head>
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


        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }


        .header {
            border-bottom: 2px solid #f18d06;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .header .left,
        .header .right {
            display: inline-block;
            vertical-align: top;
            font-size: 10px;
            color: #000;
        }

        .header .left {
            width: 60%;
        }

        .header .right {
            width: 38%;
            font-size: 10px;
        }

        table {
            width: 100%;
            text-align: left;
            border-collapse: collapse;
            line-height: inherit;
        }

        table th {
            padding: 2px 0 5px 0;
            /* vertical-align: top; */
        }

        table td {
            padding: 12px 5px;
        }

        .items-table {
            margin-bottom: 10px;
        }

        .items-table-head {
            background-color: #DCDEE3;
        }

        .items-table thead th {
            font-weight: 400;
        }

        .items-table tbody td {
            font-weight: 400 !important;
        }

        .items-table thead th:first-child {
            border-top-left-radius: 100%;
            border-bottom-left-radius: 100%;
        }

        .items-table thead th:last-child {
            border-top-right-radius: 100%;
            border-bottom-right-radius: 100%;
            padding-right: 15px;
        }

        .items-table tbody td {
            font-family: 'Exo 2', sans-serif;
            font-weight: 300;
            border-bottom: 1px solid #a3a3a3;
            padding: 5px 0 10px 8px;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 10px;
            font-weight: bold;
        }

        .summary-container {
            background-color: #DCDEE3;
            /* Grey background from image */
            border-radius: 15px;
            padding: 15px;
            margin-top: 20px;
            width: 100%;
        }

        .summary-table {
            width: 100%;
            border: none;
        }

        .note-box {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 15px;
            min-height: 60px;
            width: 60%;
            display: inline-block;
            vertical-align: top;
        }

        .totals-box {
            width: 35%;
            display: inline-block;
            vertical-align: top;
            float: right;
        }

        .totals-table {
            width: 100%;
        }

        .totals-table td {
            padding: 3px 0;
            border: none !important;
        }

        .grand-total-label {
            border-bottom: 1px solid #333;
            display: inline-block;
            padding-bottom: 2px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .tk {
            max-width: 12px;
            width: 9px;
            margin-right: 3.5px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="left">
            <h2>{{ $companyName }}</h2>
            <div>{{ $companyWebsite }}</div>
            <div>Accounts Name : <span style="font-weight: 600">{{ $supplier->name }}</span></div>
            <div>Business Name : <span style="font-weight: 600">{{ $supplier->business_name ?? '' }}</span></div>
            <div>Contact Number : <span style="font-weight: 600">{{ $supplier->phone ?? '' }}</span></div>
            <div>Address : {{ $address }}</div>
        </div>
        <div class="right">
            <h2 style="font-size: 25px; font-weight: 400; margin: 0px;">Creditors Ledger</h2>
            <div>Shop:{{ $shop }}</div>
            <div>Date :{{ $dateRange }}</div>
            <div>Phone :{{ $contactNumber }}</div>
            <div>Email :{{ $email }}</div>
        </div>
    </div>

    <table class="items-table" style="width:100%; border-collapse: collapse;">
        <thead class="items-table-head">
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Transaction Mode</th>
                <th>A/C Name</th>
                <th>Narration</th>
                <th>Chq No.</th>
                <th>Ref No.</th>
                <th class="highlight">Invoice</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ledgerEntries as $entry)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d.m.y') }}</td>
                    <td>{{ $entry['type'] }}</td>
                    <td>{{ $entry['transaction_mode'] }}</td>
                    <td>{{ $entry['account_name'] }}</td>
                    <td>{{ $entry['narration'] }}</td>
                    <td>{{ $entry['cheque_no'] }}</td>
                    <td>{{ $entry['ref_no'] }}</td>
                    <td class="highlight">{{ $entry['invoice_number'] }}</td>
                    <td class="text-right">BDT {{ number_format($entry['debit'], 2) }}</td>
                    <td class="text-right">BDT {{ number_format($entry['credit'], 2) }}</td>
                    <td class="text-right">BDT {{ number_format($entry['balance'], 2) }}</td>
                </tr>
            @endforeach

            <tr>
                <td class="text-right"><strong>Total-</strong></td>
                <td class="text-right">BDT {{ number_format($totalDebit, 2) }}</td>
                <td class="text-right">BDT {{ number_format($totalCredit, 2) }}</td>
                <td class="text-right">BDT {{ number_format($balance, 2) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="summary-container">
        <div class="note-box">
            <span style="font-weight: bold;">Note :</span>
        </div>

        <div class="totals-box">
            <div class="grand-total-label">Grand Total-</div>
            <table class="totals-table">
                <tr>
                    <td style="width: 50%;">Debit</td>
                    <td style="width: 5%;">:</td>
                    <td class="text-right">BDT {{ number_format($totalDebit, 2) }}</td>
                </tr>
                <tr>
                    <td>Credit</td>
                    <td>:</td>
                    <td class="text-right">BDT {{ number_format($totalCredit, 2) }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Closing Balance</td>
                    <td>:</td>
                    <td class="text-right" style="font-weight: bold;">
                        BDT {{ number_format($balance, 2) }}
                    </td>
                </tr>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>

</html>
