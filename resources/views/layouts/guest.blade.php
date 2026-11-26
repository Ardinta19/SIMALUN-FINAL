<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8"><meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
@include('layouts.component._page_shell')
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? 'Akun' }} · Berkah Kreatif</title>
<meta name="description" content="Verifikasi email dan pengaturan kata sandi akun Berkah Kreatif.">

{{-- Font dan aset: sama dengan halaman auth lain. Tidak ada CDN pihak ketiga
     (fonts.bunny.net dulu dipakai di sini, satu-satunya sisa Breeze). --}}
<link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}?v={{ filemtime(public_path('css/page-transitions.css')) }}">
<script src="{{ asset('js/page-transitions.js') }}?v={{ filemtime(public_path('js/page-transitions.js')) }}" defer></script>


{{-- Shell ini dulu layout bawaan Breeze: latar abu-abu, logo Laravel, dan
     font dari fonts.bunny.net. Sekarang memakai token --bk-* yang sama dengan
     modal dan kartu pelanggan, jadi tema gelap ikut tanpa tambalan per
     selektor. Tailwind/Vite tidak lagi dimuat — tiga halaman yang memakainya
     (verifikasi email, atur ulang kata sandi, konfirmasi kata sandi) sudah
     ditata di sini. --}}
<style>
    body.guest-body {
        margin: 0;
        min-height: 100%;
        background: var(--bk-page-bg);
        color: var(--bk-page-ink);
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
        font-size: var(--bk-fs-md);
        line-height: var(--bk-lh-body);
        -webkit-text-size-adjust: 100%;
    }
    .guest-shell {
        box-sizing: border-box;
        max-width: 460px;
        margin: 0 auto;
        min-height: 100vh;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--bk-space-6);
        padding: calc(var(--bk-space-7) + env(safe-area-inset-top, 0px))
                 var(--bk-space-5)
                 calc(var(--bk-space-7) + env(safe-area-inset-bottom, 0px));
    }
    .guest-shell * { box-sizing: border-box; }

    .guest-brand {
        display: inline-flex;
        align-items: center;
        gap: var(--bk-space-3);
        min-height: var(--bk-touch);
        padding: var(--bk-space-1) var(--bk-space-2);
        border-radius: var(--bk-r-control, 12px);
        color: inherit;
        text-decoration: none;
    }
    .guest-brand img {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 6px 18px rgba(84, 5, 31, .18);
    }
    .guest-brand__text {
        display: flex;
        flex-direction: column;
        font-size: var(--bk-fs-lg);
        font-weight: 800;
        line-height: var(--bk-lh-tight);
        letter-spacing: -.01em;
    }
    .guest-brand__text small {
        font-size: var(--bk-fs-2xs);
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: var(--bk-modal-ink-mid);
        margin-top: 2px;
    }
    .guest-brand:focus-visible {
        outline: 3px solid var(--bk-focus-ring);
        outline-offset: 4px;
    }

    .guest-card {
        width: 100%;
        padding: var(--bk-space-6);
        border: 1px solid var(--bk-modal-line);
        border-radius: var(--bk-r-card, 20px);
        background: var(--bk-modal-bg);
        color: var(--bk-modal-ink);
        box-shadow: var(--bk-modal-shadow);
    }

    .guest-card h1,
    .guest-card h2 {
        margin: 0 0 var(--bk-space-3);
        font-size: var(--bk-fs-lg);
        font-weight: 800;
        line-height: var(--bk-lh-tight);
        color: var(--bk-modal-ink);
        overflow-wrap: anywhere;
    }
    /* H1 di kartu tamu adalah judul halaman, bukan subjudul: satu tingkat di
       atas teks isi (15 px) supaya hierarkinya terbaca tanpa lonjakan. */
    .guest-card h1 {
        font-size: clamp(1.25rem, 1rem + 1.4vw, 1.5rem);
        letter-spacing: -.015em;
    }
    .guest-card p,
    .guest-card > div {
        margin: 0 0 var(--bk-space-4);
        color: var(--bk-modal-ink-mid);
        font-size: var(--bk-fs-md);
        line-height: var(--bk-lh-body);
    }
    .guest-card p:last-child,
    .guest-card > div:last-child { margin-bottom: 0; }

    .guest-card label {
        display: block;
        margin-bottom: var(--bk-space-2);
        font-size: var(--bk-fs-2xs);
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bk-modal-ink-mid);
    }
    .guest-card input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]) {
        width: 100%;
        min-height: var(--bk-touch);
        padding: var(--bk-space-3) var(--bk-space-4);
        border: 1.5px solid var(--bk-modal-line);
        border-radius: var(--bk-r-control, 12px);
        background: var(--bk-field-bg);
        color: var(--bk-modal-ink);
        font: inherit;
        font-size: var(--bk-fs-md);
        line-height: var(--bk-lh-snug);
        transition: border-color var(--bk-dur-1, 120ms) var(--bk-ease, ease),
                    box-shadow var(--bk-dur-1, 120ms) var(--bk-ease, ease);
    }
    .guest-card input::placeholder { color: var(--bk-modal-ink-mid); opacity: .72; }
    .guest-card input:focus-visible {
        outline: 3px solid var(--bk-focus-ring);
        outline-offset: 2px;
        border-color: var(--bk-focus-ring);
    }

    /* Daftar galat bawaan komponen x-input-error. */
    .guest-card ul {
        margin: 0 0 var(--bk-space-3);
        padding: var(--bk-space-3) var(--bk-space-4);
        list-style: none;
        border-radius: var(--bk-r-control, 12px);
        background: var(--bk-state-danger-soft);
        border: 1px solid var(--bk-state-danger-line);
        color: var(--bk-state-danger-ink);
        font-size: var(--bk-fs-sm);
        font-weight: 600;
        line-height: var(--bk-lh-snug);
    }
    .guest-card ul:last-child { margin-bottom: 0; }

    .guest-card form > div + div { margin-top: var(--bk-space-4); }
    .guest-card form + form { margin-top: var(--bk-space-3); }

    .guest-card button[type="submit"],
    .guest-card .btn-primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: var(--bk-touch);
        padding: var(--bk-space-3) var(--bk-space-4);
        border: 0;
        border-radius: var(--bk-r-control, 12px);
        background: var(--brand-500, #85092d);
        color: #fff;
        font-family: inherit;
        font-size: var(--bk-fs-sm);
        font-weight: 800;
        line-height: var(--bk-lh-tight);
        cursor: pointer;
        touch-action: manipulation;
        transition: filter var(--bk-dur-1, 120ms) var(--bk-ease, ease),
                    transform var(--bk-dur-1, 120ms) var(--bk-ease, ease);
    }
    .guest-card button[type="submit"]:hover { filter: brightness(1.06); }
    .guest-card button[type="submit"]:active { transform: scale(.985); }
    .guest-card button[type="submit"]:focus-visible {
        outline: 3px solid var(--bk-focus-ring);
        outline-offset: 2px;
    }

    .guest-card a {
        color: var(--bk-link-ink);
        font-weight: 700;
        text-underline-offset: 2px;
    }
    .guest-card a:focus-visible {
        outline: 3px solid var(--bk-focus-ring);
        outline-offset: 2px;
        border-radius: var(--bk-space-1);
    }

    /* Pesan status (mis. tautan verifikasi terkirim) memakai warna sukses. */
    .guest-success {
        padding: var(--bk-space-3) var(--bk-space-4);
        border-radius: var(--bk-r-control, 12px);
        background: var(--bk-state-success-soft);
        border: 1px solid var(--bk-state-success-line);
        color: var(--bk-state-success-ink);
        font-size: var(--bk-fs-sm);
        font-weight: 700;
        line-height: var(--bk-lh-snug);
        margin: 0 0 var(--bk-space-4);
    }

    /* Aksi sekunder (mis. "Keluar"): teks, bukan tombol penuh. */
    .guest-link-btn {
        width: 100%;
        min-height: var(--bk-touch);
        padding: var(--bk-space-2);
        border: 0;
        background: none;
        color: var(--bk-modal-ink-mid);
        font: inherit;
        font-size: var(--bk-fs-sm);
        font-weight: 700;
        text-decoration: underline;
        text-underline-offset: 2px;
        cursor: pointer;
        touch-action: manipulation;
    }
    .guest-link-btn:hover { color: var(--bk-modal-ink); }
    .guest-link-btn:focus-visible {
        outline: 3px solid var(--bk-focus-ring);
        outline-offset: 2px;
        border-radius: var(--bk-space-1);
    }

    .guest-foot {
        margin: 0;
        max-width: var(--bk-measure);
        text-align: center;
        font-size: var(--bk-fs-2xs);
        font-weight: 600;
        line-height: var(--bk-lh-snug);
        color: var(--bk-modal-ink-mid);
    }
</style>
@include('layouts.component._tokens')
@include('layouts.component._theme_head')
</head>
<body class="guest-body">
@include('layouts.component._skip_link')

<main class="guest-shell" id="main-content" tabindex="-1">
    <a class="guest-brand" href="{{ url('/') }}">
        <img src="{{ asset('images/logo-104.png') }}" alt="" width="52" height="52" decoding="async">
        <span class="guest-brand__text">Berkah Kreatif<small>Papan bunga &amp; dekorasi</small></span>
    </a>

    <div class="guest-card">
        {{ $slot }}
    </div>

    <p class="guest-foot">Sistem masih dalam pengujian dan belum untuk transaksi nyata.</p>
</main>
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>
