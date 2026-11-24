@extends('emails.layout')

@section('content')
    <p style="margin:0 0 14px;font-weight:800;font-size:16px;">Halo, {{ $name }}</p>
    <p style="margin:0 0 16px;">Kami menerima permintaan untuk mengatur ulang kata sandi akun <strong>{{ \App\Support\Laundry::name() }}</strong> kamu.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 18px;">
        <tr>
            <td style="border-radius:10px;background:#85092d;">
                <a href="{{ $url }}" target="_blank" rel="noopener"
                   style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:800;color:#ffffff;text-decoration:none;border-radius:10px;">
                    Atur Ulang Kata Sandi
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 12px;font-size:13px;color:#6f5960;">Tautan ini berlaku selama <strong>{{ $minutes }} menit</strong>. Setelah itu, silakan kirim ulang permintaan dari halaman lupa kata sandi.</p>
    <p style="margin:0 0 16px;font-size:13px;color:#6f5960;">Kalau bukan kamu yang meminta, abaikan saja email ini — kata sandi lama tetap aman.</p>

    <p style="margin:0;font-size:12px;color:#806975;word-break:break-all;">Jika tombol tidak berfungsi, salin tautan ini ke browser:<br>{{ $url }}</p>
@endsection
