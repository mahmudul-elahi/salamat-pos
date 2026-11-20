<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 30px;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
        }

        /* Header Section */
        .header-table {
            width: 100%;
            border-bottom: 4px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .logo-img {
            width: 120px;
            margin-bottom: 10px;
            display: block;
        }

        .company-info {
            font-size: 13px;
            line-height: 1.6;
            margin-top: 15px;
        }

        .voucher-type {
            text-align: right;
            vertical-align: top;
        }

        .voucher-type h1 {
            font-size: 24px;
            margin: 0;
            font-weight: normal;
            color: #000;
            letter-spacing: 1px;
        }

        /* Main Content Section */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .details-box {
            background-color: #f4f6f9;
            padding: 15px 20px;
            border-radius: 15px;
            vertical-align: top;
        }

        .amount-box {
            background-color: #f4f6f9;
            padding: 15px;
            border-radius: 15px;
            width: 18%;
            text-align: center;
            vertical-align: top;
        }

        .row-item {
            border-bottom: 1px solid #dee2e6;
            padding: 12px 0;
        }

        .inner-details-table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Footer Section */
        .footer-table {
            width: 100%;
            background-color: #dee2e6;
            border-radius: 15px;
            padding: 25px;
        }

        .totals-table {
            width: 100%;
            text-align: right;
            border-collapse: collapse;
        }

        .total-label {
            padding-right: 15px;
            text-align: right;
        }

        .barcode-img {
            width: 130px;
            height: 35px;
            margin-top: 5px;
        }
    </style>
</head>

<body>

    <div class="container">
        <table class="header-table">
            <tr>
                <td colspan="2">
                    <img src="{{ public_path('/assets/images/logo/Tekzo-logo.png') }}" class="logo-img">
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top;">
                    <div class="company-info">
                        <strong>Company Name:</strong> {{ $ledger?->contact?->business_name ?? '-' }}<br>
                        <strong>Account Name :</strong> {{ $ledger?->contact?->name ?? '-' }}<br>
                        <strong>Phone :</strong> {{ $ledger?->contact?->phone ?? '-' }}<br>
                        <strong>Email :</strong> {{ $ledger?->contact?->email ?? '-' }}<br>
                        <strong>Address :</strong> {{ $ledger?->contact?->address ?? '-' }}
                    </div>
                </td>
                <td class="voucher-type">
                    <h1>DEBIT VOUCHER</h1>
                    <div style="font-size: 12px; margin-top: 5px;">
                        Date : {{ $ledger['date'] ?? '' }}<br>

                        <div style="margin-top: 5px; text-align: right; display: inline-block;">
                            <!-- <img src="https://barcodeapi.org/api/128/{{ $voucher['voucher_no'] ?? '5415000306' }}" class="barcode-img"><br> -->
                            <div style="margin-top: 3px">
                                Voucher No : &nbsp;&nbsp;
                                <small style="letter-spacing: 1px;">{{ $ledger['full_invoice_number'] ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="content-table">
            <tr>
                <td class="details-box">
                    <table class="inner-details-table">
                        <tr>
                            <td class="row-item" width="140">Paid to</td>
                            <td class="row-item" width="15">:</td>
                            <td class="row-item">{{ $ledger?->contact?->business_name ??  $ledger?->contact?->name ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="row-item">Purpose</td>
                            <td class="row-item">:</td>
                            <td class="row-item">{{ $ledger['note'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="row-item" style="border-bottom: none;">Amount In Word</td>
                            <td class="row-item" style="border-bottom: none;">:</td>
                            <td class="row-item" style="border-bottom: none;">{{ $amountInWords ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td width="2%"></td>

                <td class="amount-box">
                    <div style="margin-bottom: 15px; font-weight: normal;">Amount</div>
                    <div style="border-top: 1px solid #ccc; padding-top: 15px; font-size: 16px;">
                        @if($ledger['debit'] > 0)
                        {{ number_format($ledger['debit']  ?? 0, 2) }}
                        @else
                        {{ number_format($ledger['credit']  ?? 0, 2) }}
                        @endif
                    </div>
                </td>

            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td width="70%" style="vertical-align: top;">
                    <div style="margin-bottom: 25px;">
                        Payment Method ({{ $ledger?->paymentMethod?->name ?? '' }})
                        : {{"BDT"}}
                        @if($ledger['debit'] > 0)
                        {{ number_format($ledger['debit']  ?? 0, 2) }}
                        @else
                        {{ number_format($ledger['credit']  ?? 0, 2) }}
                        @endif
                    </div>

                    <!-- <div style="margin-top: 20px;">
                        <strong>Checked By :</strong> {{ $voucher['checked_by'] ?? 'Showkot Hossain Shawon' }}<br>
                        <strong>Approved By :</strong> {{ $voucher['approved_by'] ?? 'Showkot Hossain Shawon' }}
                    </div> -->
                </td>
                <td width="30%" style="vertical-align: bottom;">
                    <table class="totals-table">
                        <tr>
                            <td class="total-label">Total:</td>
                            <td width="80">BDT
                                @if($ledger['debit'] > 0)
                                {{ number_format($ledger['debit']  ?? 0, 2) }}
                                @else
                                {{ number_format($ledger['credit']  ?? 0, 2) }}
                                @endif
                            </td>
                        </tr>
                        <!-- <tr>
                            <td class="total-label">Paid:</td>
                            <td>৳. {{ number_format($voucher['paid_amount'] ?? 591.00, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="total-label">Due:</td>
                            <td>৳. {{ number_format($voucher['due'] ?? 0.00, 2) }}</td>
                        </tr> -->
                    </table>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>