<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title ?? 'Daily Closing Report' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 12mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #333;
            background: #fff;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        p {
            margin: 2px 0;
        }

        /* ── Brand ── */
        .brand-text {
            font-size: 20pt;
            color: #00a09d;
            font-weight: bold;
            margin: 0;
        }

        .logo-img {
            max-width: 130px;
            height: auto;
        }

        /* ── Header Right ── */
        .report-title {
            font-size: 15pt;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #222;
            margin: 0 0 4px 0;
        }

        .date-badge {
            background-color: #f36f21;
            color: #fff;
            padding: 2px 7px;
            font-size: 9pt;
        }

        /* ── Separator ── */
        .separator {
            height: 3px;
            background-color: #f36f21;
            margin: 10px 0;
        }

        /* ── Main Table ── */
        .report-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .report-table thead tr {
            background-color: #e2e8f0;
        }

        .report-table thead th {
            color: #334155;
            padding: 8px 5px;
            font-size: 8.5pt;
            border-bottom: 2px solid #cbd5e1;
            text-align: left;
        }

        .report-table tbody td {
            padding: 7px 5px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 8.5pt;
            word-wrap: break-word;
        }

        .report-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* ── Text Helpers ── */
        .t-right {
            text-align: right;
        }

        .t-center {
            text-align: center;
        }

        .t-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .muted {
            color: #999;
        }

        .purple {
            color: #7e22ce;
            font-weight: bold;
        }

        .small {
            font-size: 7.5pt;
        }

        .orange {
            color: #f36f21;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 9999px;
            font-size: 7pt;
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

        /* ── Summary Box ── */
        .summary-td {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 12px 15px;
            text-align: right;
        }

        .summary-label {
            font-size: 9pt;
            color: #64748b;
            margin: 0 0 3px 0;
        }

        .summary-amount {
            font-size: 14pt;
            font-weight: bold;
            color: #1e293b;
            margin: 0;
        }

        /* ── Footer ── */
        .footer-table {
            width: 100%;
            border-top: 2px solid #f36f21;
            margin-top: 25px;
            padding-top: 6px;
        }

        .footer-td {
            font-size: 7.5pt;
            color: #64748b;
            padding-top: 6px;
        }

        .footer-link {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>

<body>

    {{-- ════════════════════════════════
     HEADER
════════════════════════════════ --}}
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>

            {{-- Left: Logo / Brand + Grand Total --}}
            <td width="48%" valign="top">

                @php
                $logoBase64 = '';
                $logoUrl = $tenantSettings['company_logo'] ?? null;
                if ($logoUrl) {
                try {
                $ctx = stream_context_create([
                'http' => ['timeout' => 5],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]);
                $img = @file_get_contents($logoUrl, false, $ctx);
                if ($img) {
                $ext = strtolower(pathinfo(parse_url($logoUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                $mime = match($ext) { 'jpg','jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/png' };
                $logoBase64 = "data:{$mime};base64," . base64_encode($img);
                }
                } catch (\Exception $e) {}
                }
                @endphp

                @if($logoBase64)
                <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                @else
                <p class="brand-text">{{ $tenantSettings['company_name'] ?? 'TEKZO' }}</p>
                @endif

                <p style="margin-top: 6px; font-size: 10pt; font-weight: bold; color: #1e293b;">
                    Grand Total :
                    <span class="orange">{{ number_format($closing_balance ?? 0, 2) }}</span>
                </p>

                @if(!empty($tenantSettings['company_website']))
                <p class="small" style="margin-top: 4px;">
                    <a href="{{ $tenantSettings['company_website'] }}" class="footer-link">
                        {{ $tenantSettings['company_website'] }}
                    </a>
                </p>
                @endif

            </td>

            {{-- Spacer --}}
            <td width="4%"></td>

            {{-- Right: Report Info --}}
            <td width="48%" valign="top" style="text-align: right;">

                <p class="report-title">{{ $title ?? 'Daily Closing Statement' }}</p>

                <p style="font-weight: bold; font-size: 10pt; margin: 3px 0;">
                    {{ $shop_info->name ?? '' }}
                </p>

                <p class="small muted" style="margin: 3px 0;">
                    {{ $tenantSettings['primary_address'] ?? $shop_info->address ?? '' }}
                </p>

                <p style="margin: 5px 0;">
                    Date&nbsp;:&nbsp;<span class="date-badge">{{ $date ?? now()->format('d-m-Y') }}&nbsp;{{ now()->format('H:i:s') }}</span>
                </p>

                <p class="small" style="margin-top: 4px;">
                    Phone&nbsp;: {{ $tenantSettings['primary_phone_number'] ?? $shop_info->phone ?? '-' }}
                </p>

            </td>
        </tr>
    </table>

    {{-- Separator --}}
    <div class="separator"></div>

    {{-- ════════════════════════════════
     TRANSACTIONS TABLE
════════════════════════════════ --}}
    <table class="report-table">
        <thead>
            <tr>
                <th class="t-center" style="width: 3%;">#</th>
                <th style="width: 13%;">Particular</th>
                <th style="width: 11%;">Invoice</th>
                <th style="width: 13%;">Party</th>
                <th style="width: 9%;">Payment Method</th>
                <th style="width: 9%;">Created By</th>
                <th class="t-center" style="width: 6%;">Status</th>
                <th class="t-right" style="width: 9%;">Total</th>
                <th class="t-right" style="width: 9%;">Debit</th>
                <th class="t-right" style="width: 9%;">Credit</th>
                <th class="t-right" style="width: 9%;">Balance</th>
            </tr>
        </thead>
        <tbody>

            @forelse($items as $index => $item)
            <tr>
                <td class="t-center muted">{{ $index + 1 }}</td>

                <td>{{ $item['particular'] ?? '-' }}</td>

                <td>
                    @if(!empty($item['invoice']) && $item['invoice'] !== '----')
                    <span class="purple">{{ $item['invoice'] }}</span>
                    @if(!empty($item['products']))
                    <br><span class="small muted">{{ $item['products'] }}</span>
                    @endif
                    @else
                    <span class="muted">-</span>
                    @endif
                </td>

                <td>{{ $item['contact_name'] ?? '-' }}</td>

                <td>{{ $item['payment_method'] ?? '-' }}</td>

                <td>{{ $item['created_by_name'] ?? '-' }}</td>

                <td class="t-center">
                    @php
                    $statusClass = match($item['status'] ?? null) {
                    'due' => 'status-due',
                    'partially_paid' => 'status-partial',
                    null => null,
                    default => 'status-paid',
                    };
                    @endphp
                    @if($statusClass)
                    <span class="status-badge {{ $statusClass }}">{{ $item['status'] }}</span>
                    @else
                    <span class="muted">-</span>
                    @endif
                </td>

                <td>{{ $item['total'] ?? '0.00' }}</td>

                <td>
                    @if((float)($item['debit'] ?? 0) > 0)
                    <span style="font-size:7.5pt; color:#666;"></span>{{ number_format((float)$item['debit'], 2) }}
                    @else
                    <span class="muted">—</span>
                    @endif
                </td>

                <td>
                    @if((float)($item['credit'] ?? 0) > 0)
                    <span style="font-size:7.5pt; color:#666;"></span>{{ number_format((float)$item['credit'], 2) }}
                    @else
                    <span class="muted">—</span>
                    @endif
                </td>

                <td class="bold">
                    <span style="font-size:7.5pt; color:#666;"></span>{{ number_format((float)($item['balance'] ?? 0), 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="t-center muted" style="padding: 35px 0;">
                    No transactions found for today.
                </td>
            </tr>
            @endforelse

        </tbody>
    </table>

    {{-- ════════════════════════════════
     SUMMARY ROW
════════════════════════════════ --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
        <tr>
            <td width="55%"></td>
            <td width="45%" class="summary-td">
                <p class="summary-label">Closing Balance</p>
                <p class="summary-amount">BDT {{ number_format($closing_balance ?? 0, 2) }}</p>
            </td>
        </tr>
    </table>

    {{-- ════════════════════════════════
     FOOTER
════════════════════════════════ --}}
    <table class="footer-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="footer-td" width="60%">
                @if(!empty($tenantSettings['company_website']))
                <a href="{{ $tenantSettings['company_website'] }}" class="footer-link">
                    {{ $tenantSettings['company_website'] }}
                </a>
                &nbsp;&bull;&nbsp;
                @endif
                {{ $tenantSettings['company_name'] ?? '' }}
            </td>
            <td class="footer-td t-right" width="40%">
                Daily Closing Report &mdash; {{ $date ?? now()->format('d-m-Y') }}
            </td>
        </tr>
    </table>

</body>

</html>