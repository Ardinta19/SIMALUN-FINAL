@extends('emails.layout')

@section('content')
    <p style="margin:0 0 14px;font-weight:800;font-size:16px;">Halo, {{ $name }}</p>
    <p style="margin:0 0 16px;">Kami menerima permintaan untuk mengatur ulang password akun <strong>{{ \App\Support\Laundry::name() }}</strong> kamu.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 18px;">
        <tr>
            <td style="border-radius:10px;background:#FF6B35;">
                <a href="{{ $url }}" target="_blank"
                   style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:800;color:#ffffff;text-decoration:none;border-radius:10px;">
                    Atur Ulang Password
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:13px;color:#64748b;">Tautan ini berlaku selama <strong>{{ $minutes }} menit</strong>. Setelah itu, silakan kirim ulang permintaan dari halaman lupa password.</p>
    <p style="margin:0 0 16px;font-size:13px;color:#64748b;">Kalau bukan kamu yang meminta, abaikan saja email ini — password lama tetap aman.</p>

    <p style="margin:0;font-size:12px;color:#94a3b8;word-break:break-all;">Jika tombol tidak berfungsi, salin tautan ini ke browser:<br>{{ $url }}</p>
@endsection
