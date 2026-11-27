<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">@include('layouts.component._page_shell')
<meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer">
{{-- Halaman ini sengaja tidak diindeks, tetapi tautannya dibagikan lewat
     WhatsApp; tanpa tag Open Graph pratinjaunya tampil sebagai kotak kosong. --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="Berkah Kreatif SIMALUN">
<meta property="og:locale" content="id_ID">
<meta property="og:title" content="Apresiasi · Berkah Kreatif">
<meta property="og:description" content="Lihat apresiasi pelanggan untuk papan bunga Berkah Kreatif. Rincian pesanan tetap privat.">
<meta property="og:image" content="{{ asset('images/og-default.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Apresiasi · Berkah Kreatif">
<meta name="twitter:description" content="Lihat apresiasi pelanggan untuk papan bunga Berkah Kreatif. Rincian pesanan tetap privat.">
<meta name="twitter:image" content="{{ asset('images/og-default.jpg') }}"><title>Apresiasi · Berkah Kreatif</title><link rel="stylesheet" href="{{ asset('css/berkah-auth.css') }}?v={{ filemtime(public_path('css/berkah-auth.css')) }}">@include('layouts.component._theme_head')
</head>
<body>
@include('layouts.component._skip_link')
<main class="auth-shell" id="main-content" tabindex="-1"><section class="auth-intro"><a class="auth-brand" href="{{ route('welcome') }}"><img src="{{ asset('images/logo-104.png') }}" width="104" height="57" alt=""><span>Berkah Kreatif<small>{{ \App\Support\Laundry::tagline() }}</small></span></a><div class="auth-intro-copy"><span class="auth-eyebrow">Apresiasi</span><h1>Sampaikan apresiasi.</h1><p>Sebuah apresiasi sederhana untuk momen istimewa.</p></div></section><section class="auth-main"><div class="auth-card"><span class="auth-eyebrow">Suka publik</span><h2>{{ $likes }} suka</h2><p>Untuk menjaga privasi, halaman ini tidak menampilkan rincian maupun identitas pemilik pesanan.</p>
@if(auth()->check() && auth()->user()->role === 'customer')<form method="post" action="{{ route('rental.order.like', $token) }}">@csrf<input type="hidden" name="liked" value="{{ $liked ? '0' : '1' }}"><button type="submit" class="auth-button" aria-pressed="{{ $liked ? 'true' : 'false' }}"><span class="bk-i" aria-hidden="true">@include('layouts.component._icon', ['name' => 'heart', 'size' => 20])</span> {{ $liked ? 'Batalkan suka' : 'Beri suka' }}</button></form>@elseif(!auth()->check())<p>Masuk sebagai pelanggan untuk memberi suka. Simpan tautan ini agar bisa kembali setelah masuk.</p><a class="auth-button" href="{{ route('login') }}">Masuk</a>@else<p>Masuk sebagai pelanggan untuk memberi suka.</p>@endif
</div></section></main>@include('layouts.component._ui_kit')
</body></html>
