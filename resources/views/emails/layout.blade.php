<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>{{ \App\Support\Laundry::name() }}</title>
</head>
<body style="margin:0;padding:0;background:#faf4f3;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#321d24;-webkit-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf4f3;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(84,5,31,.08);">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#85092d;background:linear-gradient(135deg,#54051f 0%,#b6435e 100%);padding:26px 28px;text-align:center;">
                            <div style="font-size:21px;font-weight:800;color:#ffffff;letter-spacing:-.3px;">{{ \App\Support\Laundry::name() }}</div>
                            <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.82);letter-spacing:2px;text-transform:uppercase;margin-top:5px;">{{ \App\Support\Laundry::tagline() }}</div>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td style="padding:28px 28px 12px;font-size:15px;line-height:1.65;color:#321d24;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:18px 28px 26px;border-top:1px solid #f0e4e0;">
                            <div style="font-size:12px;color:#6f5960;line-height:1.7;">
                                <strong style="color:#321d24;">{{ \App\Support\Laundry::name() }}</strong><br>
                                @if(\App\Support\Laundry::hasAddress()){{ \App\Support\Laundry::address() }}<br>@endif
                                WhatsApp/Telp: {{ \App\Support\Laundry::phoneDisplay() }}
                            </div>
                            <div style="font-size:11px;color:#806975;margin-top:12px;">
                                &copy; {{ date('Y') }} {{ \App\Support\Laundry::name() }}. Email otomatis — mohon tidak dibalas.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
