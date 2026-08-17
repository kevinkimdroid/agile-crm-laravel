<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;">
                    <tr>
                        <td style="background:#202665;color:#ffffff;padding:18px 24px;">
                            <p style="margin:0;font-size:11px;letter-spacing:0.06em;text-transform:uppercase;opacity:0.85;">{{ $brandShort }}</p>
                            <h1 style="margin:6px 0 0 0;font-size:18px;line-height:1.3;font-weight:700;">{{ $isNewAccount ? 'Set your CRM password' : 'Password reset' }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px 0;font-size:15px;">Hello {{ $displayName }},</p>
                            @if($isNewAccount)
                                <p style="margin:0 0 12px 0;font-size:14px;line-height:1.5;">An account was created for you on the {{ $brandShort }} CRM. Sign in with username <strong>{{ $loginUsername }}</strong>.</p>
                            @else
                                <p style="margin:0 0 12px 0;font-size:14px;line-height:1.5;">A password reset was requested for your {{ $brandShort }} CRM account ({{ $email }}).</p>
                            @endif
                            <p style="margin:0 0 18px 0;font-size:14px;line-height:1.5;">Use this button to {{ $isNewAccount ? 'choose' : 'set' }} a new password. The link expires in {{ $expiryMinutes }} minutes.</p>
                            <p style="margin:0 0 18px 0;text-align:center;">
                                <a href="{{ $resetUrl }}" style="display:inline-block;background:#202665;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 22px;border-radius:6px;">{{ $isNewAccount ? 'Set password' : 'Reset password' }}</a>
                            </p>
                            <p style="margin:0 0 8px 0;font-size:12px;color:#64748b;">If the button does not work, copy this address into your browser:</p>
                            <p style="margin:0 0 16px 0;font-size:12px;word-break:break-all;color:#202665;">{{ $resetUrl }}</p>
                            <p style="margin:0;font-size:13px;color:#64748b;">If you did not request this, you can ignore the email. Your password will not change.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:14px 24px;border-top:1px solid #e2e8f0;font-size:11px;color:#94a3b8;">
                            This is a transactional message from {{ $brandName }} CRM. It is not marketing.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
