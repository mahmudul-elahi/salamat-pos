<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Customer Ledger</title>
    <style>
        @page {
            margin: 20px 30px;
        }

        body {
            font-family: 'sans-serif';
            font-size: 11px;
            color: #333;
            font-weight: normal;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-section {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #ff8c00;
            /* Distinct Orange Line */
            padding-bottom: 15px;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-info h2 {
            margin: 0;
            color: #008080;
            /* Teal color */
            font-size: 24px;
            font-weight: normal;
            text-transform: uppercase;
        }

        .company-info .website {
            color: #555;
            margin-bottom: 15px;
            display: block;
            font-size: 11px;
        }

        .customer-details {
            margin-top: 5px;
            line-height: 1.6;
            font-size: 11px;
        }

        .customer-details div {
            margin-bottom: 3px;
        }

        .title-section h1 {
            margin: 0 0 10px 0;
            text-transform: uppercase;
            font-size: 20px;
            text-align: right;
            font-weight: normal;
            color: #000;
        }

        .shop-details {
            text-align: right;
            line-height: 1.6;
            font-size: 11px;
            color: #333;
        }

        .date-section {
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: right;
            line-height: 24px;
            /* Align text with pill height */
        }

        .date-pill {
            background-color: #FFA500;
            /* Orange */
            color: #000;
            padding: 2px 15px;
            border-radius: 50px;
            /* Fully rounded */
            font-weight: normal;
            display: inline-block;
            font-size: 11px;
            vertical-align: middle;
            margin-left: 5px;
        }

        /* Ledger Table */
        .ledger-table {
            width: 100%;
            margin-top: 15px;
        }

        .ledger-table th {
            background-color: #e6e6e6;
            /* Light Grey BG */
            padding: 8px 6px;
            text-align: left;
            font-weight: normal;
            font-size: 11px;
            color: #000;
            border: none;
        }

        .ledger-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #ccc;
            font-size: 11px;
            color: #333;
        }

        /* Column Widths */
        .ledger-table th:first-child,
        .ledger-table td:first-child {
            width: 8%;
        }

        /* Date */
        .ledger-table th:nth-child(2),
        .ledger-table td:nth-child(2) {
            width: 8%;
        }

        /* Type */
        /* ... */

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .invoice-link {
            color: #4a90e2;
            text-decoration: none;
        }

        /* Footer */
        .footer-container {
            margin-top: 30px;
            width: 100%;
            background-color: #e6e6e6;
            /* Grey background for entire footer */
            border-radius: 15px;
            /* Rounded corners for the container */
            padding: 15px 20px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .note-section {
            width: 60%;
            font-size: 11px;
            color: #333;
            padding-right: 20px;
        }

        .totals-section {
            width: 40%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 2px 0;
            border: none;
            font-size: 11px;
            color: #333;
            vertical-align: top;
        }

        .totals-table tr:first-child td {
            padding-bottom: 10px;
            font-size: 11px;
        }

        .tk {
            width: 9px;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div class="header-section">
        <table class="header-table">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    <div class="company-info">
                        @if(!empty($tenantSettings['company_logo']))
                        <img src="{{ $tenantSettings['company_logo'] }}" style="max-height: 40px; margin-bottom: 5px;"
                            alt="Logo">
                        <br>
                        @endif
                        @if(!empty($tenantSettings['company_website']))
                        <span class="website">{{ $tenantSettings['company_website'] }}</span>
                        @endif
                    </div>

                    <div class="customer-details">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 100px; padding: 1px 0;">Accounts Name</td>
                                <td style="width: 10px; padding: 1px 0;">:</td>
                                <td style="padding: 1px 0; font-weight: bold;">
                                    {{ $contactInfo->name }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 1px 0;">Business Name</td>
                                <td style="padding: 1px 0;">:</td>
                                <td style="padding: 1px 0; font-weight: bold;">{{ $contactInfo->business_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 1px 0;">Contact Number</td>
                                <td style="padding: 1px 0;">:</td>
                                <td style="padding: 1px 0; font-weight: bold;"> {{ $contactInfo->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="vertical-align: top; padding: 1px 0;">Address</td>
                                <td style="vertical-align: top; padding: 1px 0;">:</td>
                                <td style="vertical-align: top; padding: 1px 0;">{{ $contactInfo->address ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 45%; vertical-align: top;" class="shop-details">
                    <div class="title-section">
                        <h1>CREDITORS LEDGER</h1>
                    </div>
                    @if(!empty($tenantSettings['primary_address']))
                    <div style="font-size: 10px; color: #333; margin-top: 3px; margin-bottom: 15px;">
                        {{$tenantSettings['primary_address']}}
                    </div>
                    @endif

                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="text-align: right; padding: 3px 0; vertical-align: middle;">Date</td>
                            <td style="width: 10px; text-align: center; padding: 3px 0; vertical-align: middle;">:</td>
                            <td style="width: 130px; text-align: right; padding: 3px 0;">
                                <span class="date-pill">{{ $dateRange }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right; padding: 1px 0;">Phone</td>
                            <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                            @if(!empty($tenantSettings['primary_phone_number']))
                            <td style="text-align: right; padding: 1px 0;">{{ $tenantSettings['primary_phone_number'] }}</td>
                            @endif
                        </tr>
                        <tr>
                            <td style="text-align: right; padding: 1px 0;">Email</td>
                            <td style="width: 10px; text-align: center; padding: 1px 0;">:</td>
                            @if(!empty($tenantSettings['business_email']))
                            <td style="text-align: right; padding: 1px 0;">{{ $tenantSettings['business_email']}}</td>
                            @endif
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Table -->
    <table class="ledger-table">
        <thead>
            <tr>
                <th>Date</th>

                <!-- <th>A/C Name</th>
                <th>Narration</th> -->
                <th>Invoice</th>
                <th>Type</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @php
            $balance = 0;
            @endphp
            @foreach ($ledgerEntries as $entry)
            @php
            $balance += $entry->debit - $entry->credit;
            @endphp
            <tr>
                <td>{{ \Carbon\Carbon::parse($entry->date)->format('d-m-Y') }}</td>

                <!-- <td>{{ $entry->ac_name }}</td>
                <td>{{ $entry->note }}</td> -->
                <td>{{ $entry->full_invoice_number }}</td>
                <td>{{ $entry->type }}</td>
                <td>BDT {{ number_format($entry->debit, 2) }}</td>
                <td>BDT {{ number_format($entry->credit, 2) }}</td>
                <td>BDT {{ number_format($balance, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer-container">
        <table class="footer-table">
            <tr>
                <td class="note-section">
                    <div class="note-box"
                        style="background-color: #fff; border-radius: 10px; padding: 15px; height: 60px;">
                        Note :
                        <br>
                        <!-- Empty space for note -->
                    </div>
                </td>
                <td class="totals-section">
                    <table class="totals-table" style="margin-left: 20px;">
                        <tr>
                            <td colspan="3" style="padding-bottom: 5px;">
                                <span style="border-bottom: 1px solid #333; padding-bottom: 2px;">Grand Total-</span>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 30%;">Debit</td>
                            <td style="text-align: center;">:</td>
                            <td style="width: 70%; text-align: left;">
                                BDT {{ number_format($totalDebit, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Credit</td>
                            <td style="text-align: center;">:</td>
                            <td style="text-align: left;">
                                BDT {{ number_format($totalCredit, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Closing Balance</td>
                            <td style="text-align: center;">:</td>
                            <td style="text-align: left;">
                                BDT {{ number_format($balance, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>