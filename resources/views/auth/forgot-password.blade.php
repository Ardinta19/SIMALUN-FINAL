<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@if(isset($token)) Buat kata sandi baru @else Pemulihan kata sandi @endif · Berkah Kreatif</title>
<link rel="icon" href="{{ asset('images/berkah-kreatif-logo.jpeg') }}">
<link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}?v={{ filemtime(public_path('css/page-transitions.css')) }}"><script src="{{ asset('js/page-transitions.js') }}?v={{ filemtime(public_path('js/page-transitions.js')) }}" defer></script><link rel="stylesheet" href="{{ asset('css/berkah-auth.css') }}?v={{ filemtime(public_path('css/berkah-auth.css')) }}">
@include('layouts.component._theme_head')
</head>
<body class="auth-page">
@php
$oldText = function (string $key, $fallback = '') {
    $value = old($key, $fallback);
    return is_scalar($value) ? (string) $value : '';
};
@endphp
<main class="auth-shell">
    <section class="auth-intro"><a href="{{ route('welcome') }}" class="auth-brand"><img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt=""><span>Berkah Kreatif<small>Papan bunga untuk setiap cerita</small></span></a><div class="auth-intro-copy"><span class="auth-eyebrow">RUANG PELANGGAN</span><h1>Satu langkah kembali ke akunmu.</h1><p>Jaga akses ke pesanan dan kabar untuk momen istimewamu.</p></div><span class="auth-flower bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></section>
    <section class="auth-main" aria-labelledby="auth-title"><div class="auth-card">
        <a class="auth-back" href="{{ route('login') }}">@include('layouts.component._icon', ['name' => 'arrow-left', 'size' => 20])<span>Kembali ke masuk</span></a>
        @if(session('password_reset_success'))
            <span class="auth-eyebrow">SELESAI</span><h2 id="auth-title">Kata sandi berhasil diubah</h2><p>Kamu dapat masuk menggunakan kata sandi yang baru.</p><a class="auth-button" href="{{ route('login') }}">Masuk ke akun</a>
        @elseif(isset($token))
            <span class="auth-eyebrow">PEMULIHAN AKUN</span><h2 id="auth-title">Buat kata sandi baru</h2><p>Isi email akunmu dan pilih kata sandi yang baru.</p>
            @if($errors->any())<div class="auth-alert is-error" role="alert">Periksa kembali isian di bawah.</div>@endif
            <form method="post" action="{{ route('password.update.reset') }}" class="auth-form">@csrf<input type="hidden" name="token" value="{{ $token }}">
                <label for="reset-email">Email akun</label><input id="reset-email" type="email" name="email" value="{{ $oldText('email', $email ?? '') }}" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror>@error('email')<p class="auth-error" id="reset-email-error">{{ $message }}</p>@enderror
                <label for="new-password">Kata sandi baru</label><input id="new-password" type="password" name="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="reset-password-error" @enderror>@error('password')<p class="auth-error" id="reset-password-error">{{ $message }}</p>@enderror
                <label for="confirm-password">Ulangi kata sandi baru</label><input id="confirm-password" type="password" name="password_confirmation" autocomplete="new-password" required>
                <button class="auth-button" type="submit">Simpan kata sandi</button>
            </form>
        @else
            <span class="auth-eyebrow">PEMULIHAN AKUN</span><h2 id="auth-title">Lupa kata sandi?</h2><p>Masukkan email yang digunakan untuk akunmu. Jika terdaftar, kami akan mengirim tautan pemulihan.</p>
            @if(session('status'))<div class="auth-alert" role="status">Jika email terdaftar, tautan pemulihan telah dikirim. Periksa kotak masuk dan folder spam.</div>@endif
            @if(session('error'))<div class="auth-alert is-error" role="alert">{{ session('error') }}</div>@endif
            <form method="post" action="{{ route('password.email') }}" class="auth-form">@csrf
                <label for="recovery-email">Email akun</label><input id="recovery-email" type="email" name="email" value="{{ $oldText('email') }}" placeholder="nama@contoh.com" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="recovery-email-error" @enderror>
                @error('email')<p class="auth-error" id="recovery-email-error">{{ $message }}</p>@enderror
                <button class="auth-button" type="submit">Kirim tautan pemulihan</button>
            </form>
        @endif
    </div><p class="auth-foot">© {{ date('Y') }} Berkah Kreatif</p></section>
</main>
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>
