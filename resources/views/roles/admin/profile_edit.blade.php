<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
@include('layouts.component._page_shell')
<title>Edit Profil Admin · Berkah Kreatif</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   BERKAH KREATIF · ADMIN · EDIT PROFIL
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens
   emitted by layouts.component._tokens (mirrors tailwind.config.js).
═══════════════════════════════════════════════════════════ */
:root {
    /* Palet merek datang dari _tokens.blade.php (wine), tidak lagi
       ditimpa per halaman. */
    --ink:        var(--brand-900);
    --ink-muted:  var(--surface-400);
    --line:       var(--surface-200);
    --line-soft:  var(--surface-100);
    --font:       'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
    font-family: var(--font);
    font-size: 16px;
    background: var(--surface-50);
    color: var(--ink);
    min-height: 100vh;
    min-height: 100dvh;
    padding-bottom: calc(var(--space-12) + env(safe-area-inset-bottom, 0px));
    overflow-x: clip;
}

/* ── Entrance animation (pure CSS — transform/opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}.d4{animation-delay:.22s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ HEADER */
.appbar {
    max-width: 520px; margin: 0 auto;
    display: flex; align-items: center; gap: var(--space-3);
    padding: max(env(safe-area-inset-top, 0px), var(--space-4)) var(--space-4) var(--space-3);
}
.icon-btn {
    width: var(--space-11); height: var(--space-11); border-radius: var(--radius-pill);
    background: var(--brand-100); border: 1px solid var(--surface-200);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand-900); text-decoration: none; flex-shrink: 0;
    transition: transform .12s ease, background .15s ease;
}
.icon-btn:active { transform: scale(.94); background: var(--surface-100); }
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; margin: 0; }

/* ═══════════════════════════════════════════════ WRAP */
.wrap { max-width: 520px; margin: 0 auto; padding: 0 var(--space-4); }

/* ── Alert ── */
.alert {
    border-radius: var(--radius-btn);
    padding: var(--space-3) var(--space-4);
    margin-bottom: var(--space-4);
    font-size: var(--adm-fs-sm);
    font-weight: 700;
    display: flex; align-items: center; gap: var(--space-2);
}
.alert-success {
    background: color-mix(in srgb, var(--success-500) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--success-500) 28%, transparent);
    color: var(--success-500);
}
.alert svg { flex-shrink: 0; }

/* ── Card ── */
.card {
    background: var(--surface-raised);
    border: 1px solid var(--surface-200);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-card);
    padding: var(--space-5);
    margin-bottom: var(--space-4);
}
.card-title {
    display: flex; align-items: center; gap: var(--space-2);
    font-weight: 800; font-size: 1.1rem; color: var(--ink);
}
.card-title svg { color: var(--brand-500); }
.card-sub { font-size: var(--adm-fs-xs); font-weight: 600; color: var(--ink-muted); margin-top: var(--space-1); margin-bottom: var(--space-4); }

/* ── Avatar ── */
.avatar-edit-wrap { display: flex; flex-direction: column; align-items: center; margin-bottom: var(--space-5); }
.avatar-preview {
    width: 90px; height: 90px;
    border-radius: var(--radius-card);
    object-fit: cover;
    border: 3px solid var(--surface-raised);
    box-shadow: var(--shadow-card);
}
.avatar-file {
    display: inline-flex; align-items: center; gap: var(--space-2);
    margin-top: var(--space-3);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-pill);
    background: var(--brand-100);
    color: var(--brand-500);
    border: 1px solid var(--surface-200);
    font-family: var(--font); font-size: var(--adm-fs-xs); font-weight: 800;
    cursor: pointer;
    transition: transform .12s ease, background .15s ease;
}
.avatar-file:active { transform: scale(.96); }
.avatar-file input[type="file"] { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); border: 0; }
.avatar-file__name { font-size: var(--adm-fs-2xs); font-weight: 600; color: var(--ink-muted); margin-top: var(--space-2); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ── Form ── */
.form-group { margin-bottom: var(--space-4); }
.form-label {
    display: block; font-size: var(--adm-fs-2xs); font-weight: 800; color: var(--ink-muted);
    letter-spacing: .4px; margin-bottom: var(--space-2); text-transform: uppercase; padding-left: var(--space-1);
}
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon {
    position: absolute; left: var(--space-3); top: 50%; transform: translateY(-50%);
    color: var(--brand-500); pointer-events: none; display: flex;
}
.form-input {
    width: 100%;
    min-height: 48px;
    padding: var(--space-3) var(--space-4) var(--space-3) var(--space-10);
    background: var(--surface-raised);
    border: 1.5px solid var(--surface-200);
    border-radius: var(--radius-btn);
    color: var(--ink);
    font-family: var(--font);
    font-size: var(--adm-fs-md);
    font-weight: 600;
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.form-input::placeholder { color: var(--ink-muted); font-weight: 500; }
.form-input:focus { border-color: var(--brand-500); box-shadow: 0 0 0 3px rgba(0,119,182,.18); }

/* ── Button ── */
.btn {
    width: 100%;
    min-height: 48px;
    padding: var(--space-3);
    border: none;
    border-radius: var(--radius-pill);
    font-family: var(--font);
    font-weight: 800;
    font-size: var(--adm-fs-md);
    letter-spacing: .3px;
    cursor: pointer;
    transition: transform .12s ease, box-shadow .15s ease;
}
.btn:active { transform: scale(.98); }
.btn-pri { background: var(--accent-500); color: var(--surface-raised); box-shadow: var(--shadow-card); }
.btn-brand { background: var(--brand-500); color: var(--surface-raised); box-shadow: var(--shadow-card); }

.footer-text {
    text-align: center; margin-top: var(--space-4);
    font-size: var(--adm-fs-2xs); font-weight: 700; color: var(--ink-muted); letter-spacing: 1px; text-transform: uppercase;
}

/* Layar lebar: `layouts.component.admin._navbar_admin` sudah naik menjadi baris
   menu di ≥900 px, jadi isi halaman ikut melebar (960 px, sejajar halaman admin
   ber-layout). Sebelumnya halaman ini tetap 520 px sehingga tampak seperti
   kolom ponsel di tengah desktop. */
@media (min-width: 900px) {
    .appbar, .wrap { max-width: 960px; }
}

/* Halaman admin mandiri ini masih memakai palet terang `_tokens` (belum
   dimigrasikan ke `.adm-app`). Saat tema gelap aktif, tema global mengubah
   <body> dan judul menjadi terang sehingga judul di kartu putih tidak
   terbaca. Dua variabel tema dipatok ke nilai terang di halaman ini saja,
   jadi seluruh warna turunan tema ikut terang dan tampilan tetap konsisten
   sampai migrasi ke `layouts.admin` dikerjakan. */
html[data-theme="dark"] body.adm-standalone {
    --theme-bg: var(--surface-50);
    --theme-ink: var(--brand-900);
}
</style>
@include('layouts.component._theme_head')
<link rel="stylesheet" href="{{ asset('css/berkah-admin.css') }}?v={{ filemtime(public_path('css/berkah-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}?v={{ filemtime(public_path('css/page-transitions.css')) }}">
<script src="{{ asset('js/page-transitions.js') }}?v={{ filemtime(public_path('js/page-transitions.js')) }}" defer></script>
</head>
<body class="adm-standalone">
@include('layouts.component._skip_link')


<header class="appbar reveal d1">
    <a data-smart-back href="{{ \App\Support\BackUrl::resolve(request(), 'dashboard.admin') }}" id="btn-back" class="icon-btn" aria-label="Kembali">
        @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
    </a>
    <h1 class="appbar__title">Edit Profil Admin</h1>
</header>

@php
    // Nilai lama bisa berupa larik bila permintaan dikirim dengan nama field
    // ganda (mis. name[]=...). Tanpa penjaga ini halaman mencetak teks larik.
    $oldText = function (string $key, $fallback = '') {
        $value = old($key, $fallback);
        return is_scalar($value) ? (string) $value : '';
    };
@endphp

<div class="wrap" id="main-content" tabindex="-1" role="main">

    @if(session('status') === 'profile-updated')
        <div class="alert alert-success reveal d1">
            @include('layouts.component._icon', ['name' => 'success', 'size' => 20])
            Profil admin berhasil diperbarui!
        </div>
    @endif

    {{-- ══════════════ DATA IDENTITAS ══════════════ --}}
    <div class="card reveal d2">
        <div class="card-title">
            @include('layouts.component._icon', ['name' => 'profile', 'size' => 20])
            Data Identitas
        </div>
        <div class="card-sub">Perbarui informasi dasar admin</div>

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('patch')

            <div class="avatar-edit-wrap">
                <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('images/berkah-kreatif-logo.jpeg') }}" class="avatar-preview" id="preview" alt="{{ $user->name }}">
                <label for="avatar-input" class="avatar-file">
                    @include('layouts.component._icon', ['name' => 'avatar', 'size' => 16])
                    Ganti Foto
                    <input type="file" id="avatar-input" name="avatar" accept="image/*" onchange="previewImage(this)">
                </label>
                <span class="avatar-file__name" id="avatar-name"></span>
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Nama Lengkap</label>
                <div class="input-wrap">
                    <span class="input-icon">@include('layouts.component._icon', ['name' => 'profile', 'size' => 16])</span>
                    <input placeholder="Contoh: Sari Wulandari" type="text" id="name" name="name" class="form-input" value="{{ $oldText('name', $user->name) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Alamat Email</label>
                <div class="input-wrap">
                    <span class="input-icon">@include('layouts.component._icon', ['name' => 'message-circle', 'size' => 16])</span>
                    <input placeholder="Contoh: nama@email.com" autocomplete="email" type="email" id="email" name="email" class="form-input" value="{{ $oldText('email', $user->email) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Nomor WhatsApp</label>
                <div class="input-wrap">
                    <span class="input-icon">@include('layouts.component._icon', ['name' => 'phone', 'size' => 16])</span>
                    <input placeholder="Contoh: 0812 3456 7890" type="tel" id="phone" name="phone" autocomplete="tel" class="form-input" value="{{ $oldText('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <button type="submit" class="btn btn-pri">Simpan Perubahan</button>
        </form>
    </div>

    {{-- ══════════════ GANTI PASSWORD ══════════════ --}}
    <div class="card reveal d3">
        <div class="card-title">
            @include('layouts.component._icon', ['name' => 'settings', 'size' => 20])
            Ganti Kata Sandi
        </div>
        <div class="card-sub">Amankan akses panel administrator</div>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="form-group">
                <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                <div class="input-wrap">
                    <span class="input-icon">@include('layouts.component._icon', ['name' => 'eye', 'size' => 16])</span>
                    <input autocomplete="current-password" type="password" id="current_password" name="current_password" class="form-input" placeholder="••••••••">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Kata Sandi Baru</label>
                <div class="input-wrap">
                    <span class="input-icon">@include('layouts.component._icon', ['name' => 'settings', 'size' => 16])</span>
                    <input autocomplete="new-password" type="password" id="password" name="password" class="form-input" placeholder="Min. 8 karakter">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                <div class="input-wrap">
                    <span class="input-icon">@include('layouts.component._icon', ['name' => 'check-circle', 'size' => 16])</span>
                    <input autocomplete="new-password" type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Ulangi kata sandi">
                </div>
            </div>

            <button type="submit" class="btn btn-brand">Perbarui Kata Sandi</button>
        </form>
    </div>

    <div class="footer-text reveal d4">SIMALUN · Panel Admin</div>
</div>

@include('layouts.component._form_controls')

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        var nameEl = document.getElementById('avatar-name');
        if (nameEl) nameEl.textContent = input.files[0].name;
    }
}
</script>

@include('layouts.component._ui_kit')
<script src="{{ asset('js/berkah-admin.js') }}" defer></script>
</body>
</html>
