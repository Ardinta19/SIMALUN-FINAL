@extends('emails.layout')

@section('content')
    <p style="margin:0 0 14px;font-weight:800;font-size:16px;">Halo, {{ $customerName }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="background:{{ $statusColor }};border-radius:99px;padding:6px 16px;font-size:12px;font-weight:800;color:#ffffff;text-transform:uppercase;letter-spacing:.4px;">
                {{ $title }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px;">{{ $bodyText }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8fc;border:1px solid #e2eefb;border-radius:12px;margin:0 0 18px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:12px;color:#64748b;font-weight:700;">Kode Pesanan</div>
                <div style="font-size:18px;font-weight:800;color:#0077b6;letter-spacing:.5px;">#{{ $orderCode }}</div>
                <div style="font-size:13px;color:#3d5066;font-weight:700;margin-top:6px;">Status saat ini: {{ $statusLabel }}</div>
            </td>
        </tr>
    </table>

    @if(!empty($url))
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
        <tr>
            <td style="border-radius:10px;background:#0077b6;">
                <a href="{{ $url }}" target="_blank"
                   style="display:inline-block;padding:12px 26px;font-size:14px;font-weight:800;color:#ffffff;text-decoration:none;border-radius:10px;">
                    Lihat Pesanan
                </a>
            </td>
        </tr>
    </table>
    @endif
@endsection
