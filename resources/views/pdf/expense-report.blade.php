<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Expense Report</title>
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
        .t-right { text-align: right; }
        .t-center { text-align: center; }
        .t-left { text-align: left; }
        .bold { font-weight: bold; }
        .muted { color: #999; }
        .small { font-size: 7.5pt; }

        /* ── Footer ── */
        .footer-link {
            color: #2563eb;
            text-decoration: none;
        }

        .footer-container {
            margin-top: 30px;
            width: 100%;
            background-color: #e6e6e6;
            border-radius: 15px;
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
    </style>
</head>
<body>

{{-- ════════════════════════════════
     HEADER
════════════════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        {{-- Left: Logo / Brand --}}
        <td width="48%" valign="top">
            @php
                $logoBase64 = '';
                $logoUrl = $tenantSettings['company_logo'] ?? null;
                if ($logoUrl) {
                    try {
                        $ctx = stream_context_create([
                            'http' => ['timeout' => 3],
                            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
                        ]);
                        $img = @file_get_contents($logoUrl, false, $ctx);
                        if ($img && strlen($img) < 500000) {
                            $ext  = strtolower(pathinfo(parse_url($logoUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                            $mime = match($ext) {
                                'jpg', 'jpeg' => 'image/jpeg',
                                'gif'         => 'image/gif',
                                'webp'        => 'image/webp',
                                default       => 'image/png'
                            };
                            $logoBase64 = "data:{$mime};base64," . base64_encode($img);
                            unset($img);
                        }
                    } catch (\Exception $e) {}
                }
            @endphp

            @if($logoBase64)
                <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
            @else
                <p class="brand-text">{{ $tenantSettings['company_name'] ?? 'TEKZO' }}</p>
            @endif

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
            <p class="report-title">Expense Report</p>

            <p style="font-weight: bold; font-size: 10pt; margin: 3px 0;">
                {{ $shopInfo->name ?? '' }}
            </p>

            <p class="small muted" style="margin: 3px 0;">
                {{ $tenantSettings['primary_address'] ?? $shopInfo->address ?? '' }}
            </p>

            <p style="margin: 5px 0;">
                Date&nbsp;:&nbsp;<span class="date-badge">
                    {{ now()->format('d-m-Y') }}&nbsp;{{ now()->format('H:i:s') }}
                </span>
            </p>

            <p class="small" style="margin-top: 4px;">
                Phone&nbsp;: {{ $tenantSettings['primary_phone_number'] ?? '-' }}
            </p>
        </td>
    </tr>
</table>

{{-- Separator --}}
<div class="separator"></div>

{{-- ════════════════════════════════
     EXPENSE TABLE
════════════════════════════════ --}}
<table class="report-table">
    <thead>
    <tr>
        <th class="t-center" style="width: 5%;">#</th>
        <th style="width: 12%;">Date</th>
        <th style="width: 28%;">Title</th>
        <th style="width: 25%;">Category</th>
        <th style="width: 15%;">Note</th>
        <th class="t-right" style="width: 15%;">Amount</th>
    </tr>
    </thead>
    <tbody>

    @php $totalAmount = 0; @endphp

    @forelse($transactions as $index => $item)
        @php $totalAmount += (float) $item->amount; @endphp
        <tr>
            <td class="t-center muted">{{ $index + 1 }}</td>

            <td>{{ $item->date ?? '-' }}</td>

            <td>{{ $item->title ?? '-' }}</td>

            <td>{{ $item->category->name ?? '-' }}</td>

            <td class="muted" style="font-size: 7.5pt;">
                {{ $item->note ?? '-' }}
            </td>

            <td class="t-right">
                {{ number_format((float) $item->amount, 2) }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="t-center muted" style="padding: 35px 0;">
                No expense records found.
            </td>
        </tr>
    @endforelse

    </tbody>
</table>

{{-- ════════════════════════════════
     FOOTER
════════════════════════════════ --}}
<div class="footer-container">
    <table class="footer-table">
        <tr>
            <td class="note-section">
                <div style="background-color: #fff; border-radius: 10px; padding: 15px; min-height: 60px;">
                    Note :
                    <br>
                </div>
            </td>
            <td class="totals-section">
                <table class="totals-table" style="margin-left: 20px;">
                    <tr>
                        <td colspan="3" style="padding-bottom: 5px;">
                            <span style="border-bottom: 1px solid #333; padding-bottom: 2px;">Grand Total</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 40%;">Total Amount</td>
                        <td style="text-align: center; width: 10%;">:</td>
                        <td style="width: 50%; text-align: left; font-weight: bold;">
                            BDT {{ number_format($totalAmount, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
