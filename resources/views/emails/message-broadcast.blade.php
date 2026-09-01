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
                        <td align="center" style="padding-bottom:24px;">
                            <img src="{{ config('app.url') }}/assets/splash_white.png?v={{ file_exists(public_path('assets/splash_white.png')) ? filemtime(public_path('assets/splash_white.png')) : 1 }}" alt="{{ config('app.name') }}" style="max-height:56px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:20px; font-weight:600; color:#111827; padding-bottom:8px;">
                            {{ $title }}
                        </td>
                    </tr>
                    @isset($greeting)
                    <tr>
                        <td style="font-size:15px; color:#374151; padding-bottom:16px;">
                            {{ $greeting }}
                        </td>
                    </tr>
                    @endisset
                    <tr>
                        <td style="font-size:15px; line-height:1.6; color:#374151; padding-bottom:24px;">
                            {!! $body !!}
                        </td>
                    </tr>
                    @isset($link)
                    <tr>
                        <td style="padding-bottom:24px;">
                            <a href="{{ $link }}" style="display:inline-block; background-color:#4f46e5; color:#ffffff; text-decoration:none; padding:10px 20px; border-radius:6px; font-size:14px; font-weight:600;">
                                View
                            </a>
                        </td>
                    </tr>
                    @endisset
                    <tr>
                        <td style="font-size:13px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:16px;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
