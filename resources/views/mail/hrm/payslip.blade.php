<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; background: #f4f4f7; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e5e5;">
        <div style="background: #4338ca; color: #fff; padding: 20px 24px;">
            <h2 style="margin: 0;">Payslip</h2>
            <p style="margin: 4px 0 0; opacity: 0.85;">
                {{ $payslip->start_date->format('d M, Y') }} - {{ $payslip->end_date->format('d M, Y') }}
            </p>
        </div>

        <div style="padding: 24px;">
            <p style="margin: 0 0 4px;"><strong>{{ $payslip->employee?->name }}</strong></p>
            <p style="margin: 0 0 20px; color: #666;">{{ $payslip->employee?->email }}</p>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 2px solid #eee; color: #888; font-size: 12px; text-transform: uppercase;">Description</td>
                        <td style="padding: 8px 0; border-bottom: 2px solid #eee; color: #888; font-size: 12px; text-transform: uppercase;" align="right">Amount</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payslip->items as $item)
                        <tr>
                            <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                {{ $item->label }}
                                @if ($item->type === 'deduction')
                                    <span style="color: #dc3545;">(deduction)</span>
                                @endif
                            </td>
                            <td style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;" align="right">
                                {{ $item->type === 'deduction' ? '-' : '' }}{{ number_format($item->amount, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td style="padding: 16px 0 0; font-weight: bold; font-size: 16px;">Net Salary</td>
                        <td style="padding: 16px 0 0; font-weight: bold; font-size: 16px;" align="right">
                            {{ number_format($payslip->net_salary, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="background: #f9f9fb; padding: 16px 24px; color: #999; font-size: 12px;">
            This is an auto-generated payslip from {{ config('app.name') }}.
        </div>
    </div>
</body>
</html>
