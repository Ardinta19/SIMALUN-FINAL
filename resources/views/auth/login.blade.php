<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
@include('layouts.component._page_shell')
<title>Masuk · Berkah Kreatif</title>
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
html, body {
  width:100%; min-height:100%; font-family:'Plus Jakarta Sans',sans-serif;
  background:linear-gradient(168deg, #54051f 0%, #85092d 45%, #ad3553 78%, #cf7488 100%);
  overflow-x:clip;
}
#ambient { position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }
#wave-band { position:fixed; bottom:0; left:-5%; right:-5%; height:40%; pointer-events:none; z-index:0; }
.ws { position:absolute; left:0; width:200%; overflow:visible; }
.ws.w1 { bottom:38%; }
.ws.w2 { bottom:34%; opacity:.55; }
.ws.w3 { bottom:30%; opacity:.28; }
#page { position:relative; z-index:1; min-height:100vh;min-height:100dvh; display:flex; flex-direction:column; align-items:center; padding:2rem 1.2rem max(2rem, env(safe-area-inset-bottom)); }
#header { display:flex; flex-direction:column; align-items:center; gap:.35rem; padding-top:max(2.5rem, env(safe-area-inset-top)); margin-bottom:1.6rem; opacity:0; }
#drum-wrap { width:clamp(72px,16vw,92px); height:clamp(72px,16vw,92px); position:relative; }
#drum-wrap::after { content:''; position:absolute; inset:-10px; border-radius:50%; background:radial-gradient(circle, rgba(255,255,255,.14) 40%, transparent 72%); animation:halo 3.5s ease-in-out infinite; }
@keyframes halo { 0%,100%{transform:scale(1)} 50%{transform:scale(1.09)} }
#drum-spin-ring { position:absolute; inset:-5px; border-radius:50%; border:2px dashed rgba(255,255,255,.28); animation:ring-spin 6s linear infinite; pointer-events:none; }
@keyframes ring-spin { to{transform:rotate(360deg);} }
.wm-name { font-weight:800; font-size:clamp(1.6rem,6vw,2.2rem); color:#fff; letter-spacing:-.5px; text-shadow:0 3px 18px rgba(0,0,0,.28); line-height:1; }
.wm-sub  { font-size:clamp(var(--bk-fs-2xs),1.8vw,var(--bk-fs-xs)); font-weight:800; color:rgba(144,224,239,.9); letter-spacing:5px; text-transform:uppercase; margin-top:.2rem; }
.wm-bar  { width:70px; height:2.5px; background:#a92143; border-radius:99px; margin:.35rem auto 0; opacity:.75; }
#card { width:100%; max-width:420px; background:rgba(43,6,20,.55); border:1.5px solid rgba(255,255,255,.22); border-radius:24px; backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px); box-shadow:0 16px 48px rgba(0,0,0,.22), inset 0 1px 0 rgba(255,255,255,.2); padding:1.8rem 1.6rem 1.6rem; opacity:0; transform:translateY(32px); }
.card-title { font-weight:800; font-size:clamp(1.3rem,5vw,1.6rem); color:#fff; text-align:center; margin-bottom:.25rem; text-shadow:0 2px 12px rgba(0,0,0,.2); }
.card-sub { font-size:var(--bk-fs-xs); font-weight:700; color:rgba(255,255,255,.7); text-align:center; margin-bottom:1.3rem; letter-spacing:.2px; }
.form-group { margin-bottom:1rem; position:relative; }
.form-label { display:block; font-size:var(--bk-fs-2xs); font-weight:800; color:rgba(255,255,255,.85); letter-spacing:.3px; margin-bottom:.45rem; text-transform:uppercase; }
.input-wrap { position:relative; }
.input-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); display:grid; place-items:center; line-height:0; pointer-events:none; opacity:.72; }
.input-icon svg, .pw-toggle svg { display:block; }
[data-identifier-glyph][hidden], [data-glyph][hidden] { display:none; }
.form-input { width:100%; padding:.82rem .9rem .82rem 2.7rem; background:rgba(35,4,16,.42); border:1.5px solid rgba(255,255,255,.26); border-radius:12px; color:#fff; font-family:'Plus Jakarta Sans',sans-serif; font-size:var(--bk-fs-md); font-weight:600; outline:none; transition:border-color .2s, background .2s, box-shadow .2s; -webkit-appearance:none; }
.form-input::placeholder { color:rgba(255,255,255,.62); }
.form-input:focus { border-color:rgba(255,255,255,.65); background:rgba(35,4,16,.55); box-shadow:0 0 0 3px rgba(255,255,255,.1); }
.pw-toggle { position:absolute; right:13px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; font-size:1rem; color:rgba(255,255,255,.72); padding:.25rem; transition:color .2s; line-height:1; }
.pw-toggle:hover { color:rgba(255,255,255,.9); }
.form-input.error { border-color:#ff6b6b; box-shadow:0 0 0 3px rgba(255,107,107,.15); }
.form-error { font-size:var(--bk-fs-2xs); font-weight:700; color:#ffb0b0; margin-top:.35rem; display:none; padding-left:.2rem; }
.form-error.show { display:block; }
.input-error { border-color:#ff6b6b !important; }
.error-msg { font-size:var(--bk-fs-2xs); font-weight:700; color:#ffb0b0; margin-top:.35rem; padding-left:.2rem; }
.form-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.2rem; gap:.5rem; }
.remember-label { display:flex; align-items:center; gap:.5rem; cursor:pointer; font-size:var(--bk-fs-2xs); font-weight:700; color:rgba(255,255,255,.75); }
.remember-label input[type=checkbox] { flex-shrink:0; cursor:pointer; }
.forgot-link { font-size:var(--bk-fs-2xs); font-weight:800; color:rgba(144,224,239,.9); text-decoration:none; letter-spacing:.2px; white-space:nowrap; transition:color .2s; }
.forgot-link:hover { color:#fff; }
#btn-login { width:100%; padding:.9rem; border:none; border-radius:99px; background:linear-gradient(135deg,#a92143 0%,#c05a71 100%); color:#fff; font-family:'Plus Jakarta Sans',sans-serif; font-weight:900; font-size:1rem; letter-spacing:.4px; cursor:pointer; position:relative; overflow:hidden; box-shadow:0 8px 24px rgba(255,107,53,.45), inset 0 1px 0 rgba(255,255,255,.2); transition:transform .15s, box-shadow .15s; }
#btn-login::before { content:''; position:absolute; top:0; left:-100%; width:60%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent); animation:shimmer 2.8s ease-in-out infinite; }
@keyframes shimmer { 0%{left:-100%} 60%,100%{left:160%} }
#btn-login:active { transform:scale(.97); }
#btn-login:hover  { box-shadow:0 12px 32px rgba(255,107,53,.6); }
#btn-login:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.btn-spinner { display:inline-block; width:18px; height:18px; border:2.5px solid rgba(255,255,255,.35); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; vertical-align:middle; margin-right:.4rem; display:none; }
@keyframes spin { to{transform:rotate(360deg);} }
#btn-login.loading .btn-spinner { display:inline-block; }
#btn-login.loading .btn-text { opacity:.6; }
.divider { display:flex; align-items:center; gap:.8rem; margin:1.1rem 0; }
.divider::before, .divider::after { content:''; flex:1; height:1px; background:rgba(255,255,255,.2); }
.divider span { font-size:var(--bk-fs-2xs); font-weight:800; color:rgba(255,255,255,.7); letter-spacing:.5px; text-transform:uppercase; white-space:nowrap; }
.divider-or { display:flex; align-items:center; gap:.8rem; margin:1.2rem 0 .95rem; }
.divider-or::before, .divider-or::after { content:''; flex:1; height:1px; background:rgba(255,255,255,.2); }
.divider-or span { font-size:var(--bk-fs-2xs); font-weight:800; color:rgba(255,255,255,.7); letter-spacing:.5px; text-transform:uppercase; }
.btn-google { display:flex; align-items:center; justify-content:center; gap:.65rem; width:100%; padding:.82rem; border-radius:99px; background:#fff; color:#3c4043; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:var(--bk-fs-md); text-decoration:none; border:1px solid rgba(255,255,255,.6); box-shadow:0 6px 18px rgba(0,0,0,.18); transition:transform .15s, box-shadow .15s; }
.btn-google:hover { box-shadow:0 10px 26px rgba(0,0,0,.26); }
.btn-google:active { transform:scale(.97); }
.btn-google svg { width:18px; height:18px; flex-shrink:0; }
.register-row { text-align:center; margin-top:.8rem; font-size:var(--bk-fs-xs); font-weight:700; color:rgba(255,255,255,.7); }
.register-row a { color:rgba(144,224,239,.95); text-decoration:none; font-weight:900; transition:color .2s; }
.register-row a:hover { color:#fff; }
.alert { border-radius:12px; padding:.75rem 1rem; margin-bottom:1rem; font-size:var(--bk-fs-xs); font-weight:700; display:flex; align-items:center; gap:.55rem; }
.alert svg { flex:none; }
.alert-error { background:rgba(255,80,80,.18); border:1px solid rgba(255,80,80,.35); color:#ffaaaa; }
.alert-success { background:rgba(0,196,140,.18); border:1px solid rgba(0,196,140,.35); color:#7fffd4; }
#footer { margin-top:1.6rem; text-align:center; display:inline-block; padding:.3rem .9rem; border-radius:99px; background:rgba(35,4,16,.45); font-size:var(--bk-fs-2xs); font-weight:700; color:rgba(255,255,255,.72); letter-spacing:.3px; }
#btn-back { position:fixed; top:max(1rem, env(safe-area-inset-top)); left:1rem; z-index:10; width:38px; height:38px; border-radius:50%; border:none; cursor:pointer; background:rgba(255,255,255,.15); -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem; transition:background .2s, transform .15s; text-decoration:none; }
#btn-back:hover { background:rgba(255,255,255,.25); }
#btn-back:active { transform:scale(.92); }
</style>
<style>
#header{opacity:1!important} #card{opacity:1!important;transform:none!important}
#brand-logo{animation:brand-intro .95s cubic-bezier(.22,.9,.28,1) both}
.ambient-flower{position:absolute;color:rgba(255,255,255,.35);font-size:28px;animation:flower-float 5s ease-in-out infinite alternate}
@keyframes brand-intro{from{transform:rotate(-360deg) scale(.72);opacity:.4}to{transform:rotate(0) scale(1);opacity:1}}
@keyframes flower-float{from{transform:translateY(0) rotate(0)}to{transform:translateY(-14px) rotate(20deg)}}
#btn-back,.pw-toggle,#btn-login,.btn-google{min-width:44px;min-height:44px}
.pw-toggle[hidden]{display:none}
#password{padding-right:3.5rem}
:focus-visible{outline:3px solid #fff;outline-offset:3px}
@media(prefers-reduced-motion:reduce){#brand-logo,.ambient-flower,#drum-spin-ring,#drum-wrap::after,#btn-login::before{animation:none!important}}
</style>
<link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}?v={{ filemtime(public_path('css/page-transitions.css')) }}"><script src="{{ asset('js/page-transitions.js') }}?v={{ filemtime(public_path('js/page-transitions.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('css/berkah-login-splash.css') }}?v={{ file_exists(public_path('css/berkah-login-splash.css')) ? filemtime(public_path('css/berkah-login-splash.css')) : '1' }}">
@include('layouts.component._theme_head')
</head>
<body class="auth-page" @if($errors->any() || session('error') || session('success')) data-no-splash="1" @endif>
@include('layouts.component._skip_link', ['target' => 'page'])
@php
$oldText = function (string $key, $fallback = '') {
    $value = old($key, $fallback);
    return is_scalar($value) ? (string) $value : '';
};
@endphp

<div id="bk-splash" hidden role="dialog" aria-modal="true" aria-label="Membuka halaman masuk Berkah Kreatif">
  <div class="bk-splash__petals" aria-hidden="true"><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span><span class="bk-i">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></div>
  <div class="bk-splash__center">
    <div class="bk-splash__logo"><img src="{{ asset('images/logo-216.png') }}" width="216" height="118" alt="Logo Berkah Kreatif" decoding="async"></div>
    <p class="bk-splash__eyebrow">Papan Bunga &amp; Hadiah</p>
    <p class="bk-splash__brand">Berkah Kreatif</p>
    <div class="bk-splash__rule"></div>
    <p class="bk-splash__tagline">Rangkaian bunga untuk momen yang kamu tunggu.</p>
  </div>
  <div class="bk-splash__bar" aria-hidden="true"><span></span></div>
  <button class="bk-splash__skip" type="button">Lewati</button>
</div>
<script>
/* Tampilkan splash sedini mungkin (sebelum form terlihat) pada setiap kunjungan
   ke halaman login. Hanya dilewati bila: pengguna minta `?splash=0`, memakai
   prefers-reduced-motion, atau halaman ini hasil kiriman form (ada galat/notifikasi). */
(function () {
  try {
    var node = document.getElementById('bk-splash');
    if (!node) return;
    var mode = new URLSearchParams(window.location.search).get('splash');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (mode === '0' || reduce || document.body.hasAttribute('data-no-splash')) return;
    node.hidden = false;
  } catch (error) { /* biarkan: markup splash tetap tersembunyi */ }
})();
</script>

<div id="ambient" aria-hidden="true"><span class="ambient-flower bk-i" style="left:12%;top:24%">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span><span class="ambient-flower bk-i" style="right:12%;top:48%;animation-delay:-2s">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span><span class="ambient-flower bk-i" style="left:24%;bottom:12%;animation-delay:-4s">@include('layouts.component._icon', ['name' => 'flower-2', 'size' => 24])</span></div>

<div id="wave-band" aria-hidden="true">
  <svg class="ws w1" viewBox="0 0 1440 64" preserveAspectRatio="none">
    <path fill="rgba(255,255,255,.20)" d="M0,32 C200,64 400,0 600,32 C800,64 1000,0 1200,32 C1320,48 1400,20 1440,32 L1440,64 L0,64Z"/>
  </svg>
  <svg class="ws w2" viewBox="0 0 1440 64" preserveAspectRatio="none">
    <path fill="rgba(255,255,255,.14)" d="M0,16 C180,46 360,4 540,24 C720,44 900,8 1080,28 C1260,48 1380,12 1440,22 L1440,64 L0,64Z"/>
  </svg>
  <svg class="ws w3" viewBox="0 0 1440 64" preserveAspectRatio="none">
    <path fill="rgba(255,255,255,.08)" d="M0,44 C160,12 320,52 480,36 C640,20 800,50 960,32 C1120,14 1300,46 1440,30 L1440,64 L0,64Z"/>
  </svg>
</div>

<a href="{{ url('/') }}" id="btn-back" title="Kembali ke katalog" aria-label="Kembali ke katalog">&#8592;</a>

<main id="page" tabindex="-1">

  <div id="header">
    <div id="drum-wrap">
      <div id="drum-spin-ring"></div>
      <img id="brand-logo" src="{{ asset('images/logo-104.png') }}" width="104" height="57" alt="Logo Berkah Kreatif" style="width:100%;height:100%;object-fit:contain;border-radius:50%;background:white">
    </div>
    <div class="wm-name">Berkah Kreatif</div>
    <div class="wm-sub">PAPAN BUNGA&nbsp;&nbsp;•&nbsp;&nbsp;BERKAH KREATIF</div>
    <div class="wm-bar"></div>
  </div>

  <div id="card">

    <h1 class="card-title">Selamat datang</h1>
    <div class="card-sub">Masuk ke akun Berkah Kreatif kamu</div>

    @if(session('error'))
      <div class="alert alert-error" role="alert">@include('layouts.component._icon', ['name' => 'alert-triangle', 'size' => 20])<span>{{ session('error') }}</span></div>
    @endif
    
    @if(session('success'))
      <div class="alert alert-success" role="status">@include('layouts.component._icon', ['name' => 'check-circle', 'size' => 20])<span>{{ session('success') }}</span></div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" id="login-form" novalidate>
      @csrf
      
      {{-- Identifier: Email or Phone --}}
      <div class="form-group">
        <label class="form-label" for="identifier">Email / No. HP</label>
        <div class="input-wrap">
          <span class="input-icon" id="identifier-icon" aria-hidden="true"><span data-identifier-glyph="email">@include('layouts.component._icon', ['name' => 'mail', 'size' => 20])</span><span data-identifier-glyph="phone" hidden>@include('layouts.component._icon', ['name' => 'phone', 'size' => 20])</span></span>
          <input
            class="form-input @error('identifier') input-error @enderror"
            type="text"
            id="identifier"
            name="identifier"
            aria-describedby="err-identifier @error('identifier') server-identifier-error @enderror"
            @error('identifier') aria-invalid="true" @enderror
            placeholder="Contoh: nama@email.com atau 081234567890"
            value="{{ $oldText('identifier') }}"
            autocomplete="username"
            inputmode="text"
          >
        </div>
        @error('identifier')
          <div class="error-msg" id="server-identifier-error" role="alert">{{ $message }}</div>
        @enderror
        <div class="form-error" id="err-identifier" aria-live="polite"></div>
      </div>

      {{-- Kolom kata sandi --}}
      <div class="form-group">
        <label class="form-label" for="password">Kata sandi</label>
        <div class="input-wrap">
          <span class="input-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'lock', 'size' => 20])</span>
          <input
            class="form-input @error('password') input-error @enderror"
            type="password"
            id="password"
            name="password"
            aria-describedby="err-password @error('password') server-password-error @enderror"
            @error('password') aria-invalid="true" @enderror
            placeholder="Masukkan kata sandi"
            autocomplete="current-password"
          >
          <button type="button" class="pw-toggle" id="pw-toggle" hidden title="Tampilkan kata sandi" aria-label="Tampilkan kata sandi" aria-pressed="false"><span data-glyph="eye">@include('layouts.component._icon', ['name' => 'eye', 'size' => 20])</span><span data-glyph="eye-off" hidden>@include('layouts.component._icon', ['name' => 'eye-off', 'size' => 20])</span></button>
        </div>
        @error('password')
          <div class="error-msg" id="server-password-error" role="alert">{{ $message }}</div>
        @enderror
        <div class="form-error" id="err-password" aria-live="polite"></div>
      </div>

      {{-- Remember + Forgot --}}
      <div class="form-row">
        <label class="remember-label">
          <input type="checkbox" name="remember" {{ $oldText('remember') ? 'checked' : '' }}>
          Ingat saya
        </label>
        <a href="{{ route('password.request') }}" class="forgot-link">Lupa kata sandi?</a>
      </div>

      {{-- Submit Button --}}
      <button type="submit" id="btn-login">
        <span class="btn-spinner"></span>
        <span class="btn-text">Masuk sekarang</span>
      </button>
    </form>

    <div class="divider-or"><span>atau</span></div>

    <a href="{{ route('auth.google.redirect') }}" class="btn-google">
      <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
      </svg>
      Masuk dengan Google
    </a>

    <div class="divider"><span>Belum punya akun?</span></div>

    <div class="register-row">
      Daftar sebagai pelanggan &rarr;
      <a href="{{ route('register') }}">Buat Akun</a>
    </div>

  </div>

  <div id="footer">
    &copy; {{ date('Y') }} Berkah Kreatif · Papan Bunga
  </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

  /* The icon is decorative; keep a text keyboard for emails beginning with digits. */
  const identEl = document.getElementById('identifier');
  const identIcon = document.getElementById('identifier-icon');
  identEl.addEventListener('input', function(){
    const v = this.value.trim();
    const glyph = /^[0-9+]/.test(v) ? 'phone' : 'email';
    identIcon.querySelectorAll('[data-identifier-glyph]').forEach(function (node) {
      node.hidden = node.dataset.identifierGlyph !== glyph;
    });
  });

  /* ── PASSWORD TOGGLE ── */
  const pwInput = document.getElementById('password');
  const pwToggle = document.getElementById('pw-toggle');
  pwToggle.addEventListener('click', function(){
    const show = pwInput.type === 'password';
    pwInput.type = show ? 'text' : 'password';
    this.querySelector('[data-glyph="eye"]').hidden = show;
    this.querySelector('[data-glyph="eye-off"]').hidden = !show;
    this.setAttribute('aria-pressed', String(show));
    this.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    this.title = show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
  });

  pwToggle.hidden = false;

  /* ── CLIENT-SIDE VALIDATION ── */
  const form = document.getElementById('login-form');
  const btnLogin = document.getElementById('btn-login');
  const errIdent = document.getElementById('err-identifier');
  const errPw = document.getElementById('err-password');

  function showErr(el, msg){ el.textContent=msg; el.classList.add('show'); }
  function clearErr(el) { el.textContent=''; el.classList.remove('show'); }

  form.addEventListener('submit', function(e){
    let valid = true;
    const ident = identEl.value.trim();
    const pw = pwInput.value;

    clearErr(errIdent); clearErr(errPw);
    identEl.classList.remove('error');
    pwInput.classList.remove('error');

    if(!ident){
      showErr(errIdent, 'Email atau No. HP wajib diisi'); identEl.classList.add('error'); identEl.setAttribute('aria-invalid', 'true'); valid=false;
    }

    if(!pw){
      showErr(errPw, 'Kata sandi wajib diisi'); pwInput.classList.add('error'); pwInput.setAttribute('aria-invalid', 'true'); valid=false;
    }

    if(!valid){ e.preventDefault(); (!ident ? identEl : pwInput).focus(); return; }

    /* show loading state */
    btnLogin.classList.add('loading');
    btnLogin.disabled = true;
  });

  window.addEventListener('pageshow', () => { btnLogin.disabled = false; btnLogin.classList.remove('loading'); });

  /* remove error on input */
  identEl.addEventListener('input', ()=>{ clearErr(errIdent); identEl.classList.remove('error'); identEl.removeAttribute('aria-invalid'); });
  pwInput.addEventListener('input', ()=>{ clearErr(errPw); pwInput.classList.remove('error'); pwInput.removeAttribute('aria-invalid'); });

});
</script>
@include('layouts.component._form_controls')
<script src="{{ asset('js/berkah-login-splash.js') }}?v={{ file_exists(public_path('js/berkah-login-splash.js')) ? filemtime(public_path('js/berkah-login-splash.js')) : '1' }}"></script>
@include('layouts.component._ui_kit')
</body>
</html>