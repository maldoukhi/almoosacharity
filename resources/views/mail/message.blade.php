<!DOCTYPE html>
<html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 12px; overflow: hidden;">
                    <tr>
                        <td style="background-color: #1C545E; padding: 20px 32px; color: #ffffff; font-family: Tahoma, Arial, sans-serif; font-size: 18px; font-weight: bold; text-align: right;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 28px 32px; color: #1f2937; font-family: Tahoma, Arial, sans-serif; font-size: 15px; line-height: 1.9; text-align: right; white-space: pre-line;" dir="rtl">{{ $bodyText }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
