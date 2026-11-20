<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $appName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:'Segoe UI',Arial,sans-serif;color:#1a1a2e;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f2f5;padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#4338ca 0%,#6366f1 100%);border-radius:12px 12px 0 0;padding:40px 40px 32px;text-align:center;">
                        <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                            <span style="font-size:32px;line-height:1;">🎉</span>
                        </div>
                        <h1 style="margin:0 0 8px;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.5px;">
                            Welcome aboard, {{ $employee->name }}!
                        </h1>
                        <p style="margin:0;color:rgba(255,255,255,0.8);font-size:15px;">
                            Your employee account has been created at <strong>{{ $appName }}</strong>
                        </p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background:#ffffff;padding:36px 40px;">

                        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#374151;">
                            Hi <strong>{{ $employee->name }}</strong>, we're thrilled to have you on the team!
                            Your account is all set up and ready to go. Below are your login credentials —
                            please keep them safe and change your password after your first login.
                        </p>

                        {{-- Credentials box --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                            style="background:#f8f7ff;border:1px solid #e0e0f0;border-radius:10px;margin-bottom:28px;">
                            <tr>
                                <td style="padding:24px 28px;">
                                    <p style="margin:0 0 16px;font-size:12px;font-weight:700;letter-spacing:1px;
                                               text-transform:uppercase;color:#6366f1;">
                                        Your Login Credentials
                                    </p>

                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:8px 0;border-bottom:1px solid #ebebf5;">
                                                <span style="font-size:13px;color:#6b7280;">Email Address</span>
                                            </td>
                                            <td align="right" style="padding:8px 0;border-bottom:1px solid #ebebf5;">
                                                <span style="font-size:14px;font-weight:600;color:#1a1a2e;">
                                                    {{ $employee->email }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;border-bottom:1px solid #ebebf5;">
                                                <span style="font-size:13px;color:#6b7280;">Password</span>
                                            </td>
                                            <td align="right" style="padding:8px 0;border-bottom:1px solid #ebebf5;">
                                                <span style="font-size:14px;font-weight:600;color:#1a1a2e;
                                                             font-family:monospace;background:#eef2ff;
                                                             padding:2px 8px;border-radius:4px;">
                                                    {{ $plainPassword }}
                                                </span>
                                            </td>
                                        </tr>
                                        @if($employee->phone)
                                        <tr>
                                            <td style="padding:8px 0;">
                                                <span style="font-size:13px;color:#6b7280;">Phone</span>
                                            </td>
                                            <td align="right" style="padding:8px 0;">
                                                <span style="font-size:14px;font-weight:600;color:#1a1a2e;">
                                                    {{ $employee->phone }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>

                        {{-- CTA button --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $loginUrl }}"
                                        style="display:inline-block;background:linear-gradient(135deg,#4338ca,#6366f1);
                                               color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;
                                               padding:14px 40px;border-radius:8px;letter-spacing:0.3px;">
                                        Login to Your Account →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        {{-- Login URL fallback --}}
                        <p style="margin:0 0 24px;font-size:13px;color:#6b7280;text-align:center;">
                            Or copy this link:
                            <a href="{{ $loginUrl }}" style="color:#4338ca;word-break:break-all;">{{ $loginUrl }}</a>
                        </p>

                        {{-- Security notice --}}
                        <table width="100%" cellpadding="0" cellspacing="0"
                            style="background:#fff7ed;border-left:4px solid #f97316;border-radius:0 8px 8px 0;">
                            <tr>
                                <td style="padding:14px 18px;">
                                    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.6;">
                                        <strong>🔒 Security Tip:</strong>
                                        Please change your password immediately after your first login.
                                        Never share your credentials with anyone.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#f8f9fb;border-radius:0 0 12px 12px;padding:24px 40px;
                               border-top:1px solid #e5e7eb;text-align:center;">
                        <p style="margin:0 0 6px;font-size:13px;color:#9ca3af;">
                            This email was sent by <strong style="color:#4338ca;">{{ $appName }}</strong> •
                            Please do not reply to this email.
                        </p>
                        <p style="margin:0;font-size:12px;color:#d1d5db;">
                            © {{ date('Y') }} {{ $appName }}. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
