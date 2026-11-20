<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Debit Voucher</title>
</head>

<body style="font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; background: #fff; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 0; padding: 0;">
        <tr>
            <td style="padding: 30px 30px 0 30px;">
                <!-- Header Row -->
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="width: 60%; vertical-align: top;">
                            <span style="font-size: 28px; font-weight: bold; color: #1a9d8f;">TE</span><span style="font-size: 28px; font-weight: bold; color: #e63946;">K</span><span style="font-size: 28px; font-weight: bold; color: #1a9d8f;">ZO</span><br>
                            <span style="font-size: 13px;">Company Name : Tekzo</span><br>
                            <span style="font-size: 13px;">Account Name : {{ $voucher['account_name'] ?? 'Shuvo adhikary' }}</span><br>
                            <span style="font-size: 13px;">Phone : {{ $voucher['phone'] ?? '+8801897715226' }}</span><br>
                            <span style="font-size: 13px;">Email : {{ $voucher['email'] ?? 'support@tekzo.com.bd' }}</span><br>
                            <span style="font-size: 13px;">Address : {{ $voucher['address'] ?? '4A-018A Level: 4 Block: A, Jamuna Future Park, Dhaka, Bangladesh' }}</span>
                        </td>
                        <td style="width: 40%; vertical-align: top; text-align: right;">
                            <span style="font-size: 22px; font-weight: bold;">DEBIT VOUCHER</span><br>
                            <span style="font-size: 13px;">Date : {{ $voucher['date'] ?? '2024-11-18 13:39:15' }}</span><br>
                            <span style="font-size: 13px;">Voucher No : {{ $voucher['voucher_no'] ?? '5415000306' }}</span><br>
                            <span style="font-size: 11px; border: 1px solid #ccc; padding: 2px 8px; font-family: DejaVu Sans Mono, monospace; display: inline-block; margin-top: 4px;">||| {{ $voucher['voucher_no'] ?? '5415000306' }} |||</span>
                        </td>
                    </tr>
                </table>
                <div style="height: 16px;"></div>
                <hr style="border: none; border-top: 2px solid #e0e0e0; margin: 10px 0 18px 0;" />
                <!-- Details Table -->
                <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #e0e0e0; background: #f5f7fa;">
                    <tr>
                        <td style="width: 160px; font-weight: bold; color: #444;">Paid to</td>
                        <td style="width: 12px; color: #555;">:</td>
                        <td style="color: #222;">{{ $voucher['paid_to'] ?? 'Shourov Shaheriar' }}</td>
                        <td rowspan="3" style="text-align: center; font-weight: bold; font-size: 22px; color: #222; width: 120px; border-left: 1px solid #e0e0e0; background: #fff;">{{ number_format($voucher['amount'] ?? 591.00, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; color: #444;">Purpose</td>
                        <td style="color: #555;">:</td>
                        <td style="color: #222;">{{ $voucher['purpose'] ?? 'Products Purchase' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; color: #444;">Amount In Word</td>
                        <td style="color: #555;">:</td>
                        <td style="color: #222;">{{ $voucher['amount_in_words'] ?? 'One thousand five hundred seventy-two.' }}</td>
                    </tr>
                </table>
                <div style="height: 10px;"></div>
                <!-- Footer Section -->
                <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #e0e0e0; background: #e3e5ea;">
                    <tr>
                        <td colspan="2" style="font-size: 13px; color: #222; font-weight: bold;">Payment Method & (Partial Payment): ৳. {{ number_format($voucher['paid_amount'] ?? 591.00, 2) }} ({{ $voucher['payment_method'] ?? 'Cash' }})</td>
                        <td style="font-size: 13px; color: #222; font-weight: bold; text-align: right;">Total : ৳. {{ number_format($voucher['total'] ?? 591.00, 2) }}</td>
                        <td style="font-size: 13px; color: #222; font-weight: bold; text-align: right;">Paid : ৳. {{ number_format($voucher['paid_amount'] ?? 591.00, 2) }}</td>
                        <td style="font-size: 13px; color: #e63946; font-weight: bold; text-align: right;">Due : ৳. {{ number_format($voucher['due'] ?? 0.00, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="5" style="font-size: 13px; color: #222;">Checked By : {{ $voucher['checked_by'] ?? 'Showkot Hossain Shawon' }} &nbsp;&nbsp; Approved By : {{ $voucher['approved_by'] ?? 'Showkot Hossain Shawon' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>