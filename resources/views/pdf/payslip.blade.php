<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #334155;
            margin: 0;
            padding: 20px;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        tr {
            page-break-inside: avoid;
        }

        .header-container {
            width: 100%;
            margin-bottom: 20px;
        }

        .header-left {
            width: 50%;
            display: inline-block;
            vertical-align: top;
        }

        .header-right {
            width: 48%;
            display: inline-block;
            vertical-align: top;
            text-align: right;
        }

        .report-title {
            font-size: 22px;
            font-weight: 900;
            color: #1e293b;
            margin: 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .thick-line {
            width: 100%;
            height: 3px;
            background-color: #4338ca;
            margin: 10px 0 20px 0;
        }

        .info-box {
            background-color: #f8f9fb;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
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

        .items-table thead th {
            background-color: #e2e8f0;
            color: #475569;
            padding: 8px 10px;
            font-weight: 700;
            text-align: left;
            font-size: 10px;
            border: none;
        }

        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .text-right { text-align: right; }
        .text-danger { color: #dc2626; }

        .summary-box {
            margin-top: 20px;
            background-color: #1e293b;
            color: #fff;
            border-radius: 8px;
            padding: 16px 20px;
        }

        .summary-label {
            font-size: 12px;
            opacity: 0.8;
        }

        .summary-value {
            font-size: 20px;
            font-weight: 900;
            float: right;
        }

        .footer-note {
            margin-top: 30px;
            color: #94a3b8;
            font-size: 9px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header-container">
        <div class="header-left">
            @if (!empty($logo))
                <input type="image" src="{{ $logo }}" style="max-height: 40px;" alt="Logo">
            @else
                <strong>{{ config('app.name') }}</strong>
            @endif
        </div>
        <div class="header-right">
            <h1 class="report-title">Payslip</h1>
            <div>{{ $payslip->start_date->format('d M, Y') }} - {{ $payslip->end_date->format('d M, Y') }}</div>
        </div>
    </div>
    <div class="thick-line"></div>

    <div class="info-box">
        <table>
            <tr>
                <td style="width: 33%;">
                    <span class="info-label">Employee</span>
                    <span class="info-value">{{ $payslip->employee?->name }}</span>
                </td>
                <td style="width: 33%;">
                    <span class="info-label">Department</span>
                    <span class="info-value">{{ $payslip->department?->name ?? '-' }}</span>
                </td>
                <td style="width: 33%;">
                    <span class="info-label">Payrun</span>
                    <span class="info-value">{{ $payslip->payrun?->name }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslip->items as $item)
                <tr>
                    <td>
                        {{ $item->label }}
                        @if ($item->type === 'deduction')
                            <span class="text-danger">(deduction)</span>
                        @endif
                    </td>
                    <td class="text-right">
                        {{ $item->type === 'deduction' ? '-' : '' }}{{ number_format($item->amount, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <span class="summary-label">Net Salary</span>
        <span class="summary-value">{{ number_format($payslip->net_salary, 2) }}</span>
    </div>

    <div class="footer-note">
        This is a system-generated payslip and does not require a signature.
    </div>
</body>

</html>
