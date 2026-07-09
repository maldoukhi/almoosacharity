@php
    /**
     * Branded, table-based, inline-styled HTML email — the layout every
     * outbound notification/broadcast email uses. Email clients strip
     * <style> blocks, so all styling is inline; the layout is nested
     * tables so it renders consistently in Gmail/Outlook. RTL, the
     * charity's brand colours, a styled wordmark header (no hotlinked
     * assets), the (escaped) message body, and a subtle footer.
     *
     * $bodyText is the already-rendered message (template placeholders
     * substituted upstream) and is printed escaped via {{ }} — never raw.
     */
    $appName = config('app.name', 'جمعية الموسى الخيرية');
    $year = date('Y');
@endphp
<!DOCTYPE html>
<html dir="rtl" lang="{{ str_replace('_', '-', app()->getLocale()) }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $appName }}</title>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#eef1f2; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef1f2;">
        <tr>
            <td align="center" style="padding:28px 16px;">
                <!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(28,84,94,0.12);">
                    {{-- Header: teal band with a styled wordmark + green accent underline. --}}
                    <tr>
                        <td style="background-color:#1C545E; padding:26px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="right" style="font-family:Tahoma,'Segoe UI',Arial,sans-serif; color:#ffffff; font-size:20px; font-weight:bold; line-height:1.4;">
                                        {{ $appName }}
                                        <div style="height:3px; width:52px; background-color:#85BF40; border-radius:2px; margin-top:10px;"></div>
                                    </td>
                                    <td align="left" width="44" style="width:44px;">
                                        <div style="width:40px; height:40px; border-radius:50%; background-color:#85BF40; text-align:center; line-height:40px; font-family:Tahoma,Arial,sans-serif; color:#ffffff; font-size:18px; font-weight:bold;">م</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body: the escaped message text; pre-line keeps the
                         template's own line breaks. --}}
                    <tr>
                        <td dir="rtl" style="padding:30px 32px 26px; color:#1f2937; font-family:Tahoma,'Segoe UI',Arial,sans-serif; font-size:15px; line-height:1.95; text-align:right; white-space:pre-line;">{{ $bodyText }}</td>
                    </tr>

                    {{-- Accent divider. --}}
                    <tr>
                        <td style="padding:0 32px;">
                            <div style="border-top:1px solid #edf0f1; height:1px; line-height:1px; font-size:0;">&nbsp;</div>
                        </td>
                    </tr>

                    {{-- Footer. --}}
                    <tr>
                        <td style="padding:18px 32px 26px; text-align:right; font-family:Tahoma,'Segoe UI',Arial,sans-serif;">
                            <p style="margin:0; color:#6b7280; font-size:12px; line-height:1.7;">
                                {{ $appName }}
                            </p>
                            <p style="margin:6px 0 0; color:#9ca3af; font-size:11px; line-height:1.7;">
                                {{ __('notifications.mail.layout.automated_note') }}
                            </p>
                            <p style="margin:6px 0 0; color:#c0c6cb; font-size:11px; line-height:1.7;">
                                &copy; {{ $year }} {{ $appName }}
                            </p>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>
