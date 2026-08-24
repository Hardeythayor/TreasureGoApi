<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; padding:32px; max-width:600px;">
                    <tr>
                        <td style="font-size:20px; font-weight:600; color:#111827; padding-bottom:16px;">
                            New Contact Us Message
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; color:#6b7280; padding-bottom:4px;">
                            From: {{ $senderName }} ({{ $senderEmail }})
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; color:#6b7280; padding-bottom:16px;">
                            Subject: {{ $contactSubject }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:15px; line-height:1.6; color:#374151; border-top:1px solid #e5e7eb; padding-top:16px;">
                            {!! nl2br(e($contactMessage)) !!}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
