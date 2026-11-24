<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<title>Daftar – Berkah Kreatif</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
html, body {
  width:100%; min-height:100%; font-family:'Plus Jakarta Sans',sans-serif;
  background:linear-gradient(168deg, #54051f 0%, #85092d 45%, #ad3553 78%, #cf7488 100%);
  overflow-x:hidden;
}

/* ── AMBIENT ── */
#ambient { position:fixed; inset:0; pointer-events:none; z-index:0; overflow:hidden; }

/* ── WAVES ── */
#wave-band { position:fixed; bottom:0; left:-5%; right:-5%; height:40%; pointer-events:none; z-index:0; }
.ws { position:absolute; left:0; width:200%; overflow:visible; }
.ws.w1 { bottom:38%; }
.ws.w2 { bottom:34%; opacity:.55; }
.ws.w3 { bottom:30%; opacity:.28; }

/* ── PAGE ── */
#page {
  position:relative; z-index:1; min-height:100vh;min-height:100dvh;
  display:flex; flex-direction:column; align-items:center;
  padding:0 1.2rem max(2rem, env(safe-area-inset-bottom));
}

/* ── HEADER ── */
#header {
  display:flex; flex-direction:column; align-items:center;
  gap:.3rem; padding-top:max(2rem, env(safe-area-inset-top));
  margin-bottom:1.2rem; opacity:0;
}
#drum-wrap {
  width:clamp(62px,14vw,78px); height:clamp(62px,14vw,78px); position:relative;
}
#drum-wrap::after {
  content:''; position:absolute; inset:-8px; border-radius:50%;
  background:radial-gradient(circle, rgba(255,255,255,.14) 40%, transparent 72%);
  animation:halo 3.5s ease-in-out infinite;
}
@keyframes halo { 0%,100%{transform:scale(1)} 50%{transform:scale(1.09)} }
#drum-spin-ring {
  position:absolute; inset:-4px; border-radius:50%;
  border:2px dashed rgba(255,255,255,.28);
  animation:ring-spin 6s linear infinite; pointer-events:none;
}
@keyframes ring-spin { to{transform:rotate(360deg);} }
.wm-name { font-weight:800; font-size:clamp(1.4rem,5.5vw,1.9rem); color:#fff; letter-spacing:-.5px; text-shadow:0 3px 18px rgba(0,0,0,.28); line-height:1; }
.wm-sub  { font-size:clamp(var(--bk-fs-2xs),1.8vw,var(--bk-fs-xs)); font-weight:800; color:rgba(144,224,239,.9); letter-spacing:5px; text-transform:uppercase; margin-top:.18rem; }
.wm-bar  { width:60px; height:2.5px; background:#a92143; border-radius:99px; margin:.3rem auto 0; opacity:.75; }

/* ── CARD ── */
#card {
  width:100%; max-width:420px;
  background:rgba(43,6,20,.55); border:1.5px solid rgba(255,255,255,.26);
  border-radius:24px; backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px);
  box-shadow:0 16px 48px rgba(0,0,0,.22), inset 0 1px 0 rgba(255,255,255,.2);
  padding:1.6rem 1.5rem 1.5rem; opacity:0; transform:translateY(32px);
}
.card-title {
  font-weight:800; font-size:clamp(1.2rem,4.8vw,1.5rem);
  color:#fff; text-align:center; margin:0 0 .2rem; text-shadow:0 2px 12px rgba(0,0,0,.2);
}
.card-sub {
  font-size:var(--bk-fs-2xs); font-weight:700; color:rgba(255,255,255,.7);
  text-align:center; margin-bottom:1.2rem; letter-spacing:.2px;
}

/* ── STEP INDICATOR ── */
.step-indicator {
  display:flex; align-items:center; justify-content:center;
  gap:.4rem; margin-bottom:1.3rem;
}
.step-dot {
  position:relative;
  width:28px; height:28px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-weight:800; font-size:var(--bk-fs-2xs);
  border:2px solid rgba(255,255,255,.42);
  color:rgba(255,255,255,.68);
  transition:background-color .3s ease, border-color .3s ease, color .3s ease;
}
.step-dot.active {
  background:#a92143; border-color:#a92143; color:#fff;
  box-shadow:0 0 14px rgba(255,107,53,.5);
}
.step-dot.done {
  background:#087f5b; border-color:#087f5b; color:#fff;
  font-size:0;
}
/* Tanda centang digambar dari dua sisi kotak: konsisten di semua perangkat,
   tidak bergantung pada font emoji. */
.step-dot.done::after {
  content:""; width:6px; height:11px; margin-top:-2px;
  border:2px solid currentColor; border-top:0; border-left:0;
  transform:rotate(45deg);
}
.step-line {
  flex:1; max-width:40px; height:2px;
  background:rgba(255,255,255,.2); border-radius:99px;
  transition:background .3s;
}
.step-line.done { background:#00C48C; }

/* ── FORM ── */
.form-step { display:block; }
.step-indicator, .btn-next-step, .btn-back-step, .pw-toggle { display:none; }
.register-enhanced .form-step { display:none; }
.register-enhanced .form-step.active { display:block; }
.register-enhanced .step-indicator { display:flex; }
.register-enhanced .btn-next-step, .register-enhanced .btn-back-step { display:block; }
.register-enhanced .pw-toggle { display:block; }

.form-group { margin-bottom:.9rem; position:relative; }
.form-label {
  display:block; font-size:var(--bk-fs-2xs); font-weight:800;
  color:rgba(255,255,255,.85); letter-spacing:.3px;
  margin-bottom:.4rem; text-transform:uppercase;
}
.input-wrap { position:relative; }
.input-icon {
  position:absolute; left:14px; top:50%; transform:translateY(-50%);
  display:grid; place-items:center; line-height:0;
  pointer-events:none; opacity:.72;
}
.input-icon svg, .pw-toggle svg { display:block; }
[data-glyph][hidden] { display:none; }
.form-input {
  width:100%; padding:.78rem .9rem .78rem 2.65rem;
  background:rgba(35,4,16,.42); border:1.5px solid rgba(255,255,255,.26);
  border-radius:12px; color:#fff;
  font-family:'Plus Jakarta Sans',sans-serif; font-size:var(--bk-fs-md); font-weight:600;
  outline:none; transition:border-color .2s, background .2s, box-shadow .2s;
  -webkit-appearance:none;
}
.form-input::placeholder { color:rgba(255,255,255,.62); }
.form-input:focus {
  border-color:rgba(255,255,255,.65); background:rgba(35,4,16,.55);
  box-shadow:0 0 0 3px rgba(255,255,255,.1);
}
.form-input.error { border-color:#ff6b6b; box-shadow:0 0 0 3px rgba(255,107,107,.15); }

.pw-toggle {
  position:absolute; right:12px; top:50%; transform:translateY(-50%);
  background:none; border:none; cursor:pointer; font-size:var(--bk-fs-md);
  color:rgba(255,255,255,.72); padding:.25rem; transition:color .2s; line-height:1;
}
.pw-toggle:hover { color:rgba(255,255,255,.9); }

.form-error {
  font-size:var(--bk-fs-2xs); font-weight:700; color:#ffb0b0;
  margin-top:.3rem; display:none; padding-left:.2rem;
}
.form-error.show { display:block; }

/* password strength meter */
.pw-strength {
  margin-top:.4rem; display:none;
}
.pw-strength.show { display:block; }
.pw-strength-bar {
  height:4px; border-radius:99px;
  background:rgba(255,255,255,.15); overflow:hidden; margin-bottom:.3rem;
}
.pw-strength-fill {
  height:100%; border-radius:99px;
  width:0%; transition:width .3s, background .3s;
}
.pw-strength-lbl {
  font-size:var(--bk-fs-2xs); font-weight:800; color:rgba(255,255,255,.7);
  letter-spacing:.3px;
}

/* password hint checklist */
.pw-hints { list-style:none; margin-top:.4rem; display:flex; flex-wrap:wrap; gap:.3rem; }
.pw-hint {
  font-size:var(--bk-fs-2xs); font-weight:700;
  color:rgba(255,255,255,.68); padding:.15rem .45rem;
  background:rgba(255,255,255,.08); border-radius:99px;
  border:1px solid rgba(255,255,255,.15);
  transition:background-color .2s ease, border-color .2s ease, color .2s ease;
}
.pw-hint.ok { color:#5ce8bd; background:rgba(0,196,140,.12); border-color:rgba(0,196,140,.3); }

/* phone input with country code */
.phone-row { display:flex; gap:.5rem; }
.phone-prefix {
  display:flex; align-items:center; justify-content:center;
  padding:.78rem .8rem; background:rgba(255,255,255,.1);
  border:1.5px solid rgba(255,255,255,.22); border-radius:12px;
  font-size:var(--bk-fs-sm); font-weight:800; color:rgba(255,255,255,.8);
  white-space:nowrap; flex-shrink:0; letter-spacing:.2px;
}
.phone-input-wrap { flex:1; position:relative; }

/* gender select */
.gender-row { display:flex; gap:.6rem; }
.gender-opt { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
.gender-opt:focus-visible + .gender-label { outline:3px solid #fff; outline-offset:3px; }
.gender-label {
  flex:1; display:flex; flex-direction:column; align-items:center; gap:.3rem;
  padding:.65rem .4rem; background:rgba(255,255,255,.08);
  border:1.5px solid rgba(255,255,255,.2); border-radius:12px;
  cursor:pointer; transition:background-color .2s ease, border-color .2s ease, color .2s ease; text-align:center;
}
.gender-label span { font-size:var(--bk-fs-xs); font-weight:800; color:rgba(255,255,255,.78); line-height:1.35; }
.gender-opt:checked + .gender-label {
  background:rgba(255,107,53,.2); border-color:#a92143;
  box-shadow:0 0 0 2px rgba(255,107,53,.25);
}
.gender-opt:checked + .gender-label span { color:#fff; }

/* terms checkbox */
.terms-row { display:flex; align-items:flex-start; gap:.7rem; margin-top:.6rem; }
/* Rupa kotak centang diatur berkah-ui-kit.css; di sini hanya perataan. */
.terms-cb { flex-shrink:0; margin-top:2px; }
.terms-txt { font-size:var(--bk-fs-2xs); font-weight:700; color:rgba(255,255,255,.75); line-height:1.5; }
.terms-txt a { color:rgba(144,224,239,.9); text-decoration:none; font-weight:900; }

/* ── BUTTONS ── */
.btn-row { display:flex; gap:.6rem; margin-top:1.1rem; }
.btn-back-step {
  padding:.82rem 1.1rem; background:rgba(255,255,255,.12);
  border:1.5px solid rgba(255,255,255,.22); border-radius:99px;
  color:rgba(255,255,255,.8); font-family:'Plus Jakarta Sans',sans-serif;
  font-weight:800; font-size:var(--bk-fs-sm); cursor:pointer;
  transition:background .2s; white-space:nowrap;
}
.btn-back-step:hover { background:rgba(255,255,255,.2); }

.btn-next-step, .btn-submit {
  flex:1; padding:.85rem; border:none; border-radius:99px;
  background:linear-gradient(135deg,#a92143 0%,#c05a71 100%);
  color:#fff; font-family:'Plus Jakarta Sans',sans-serif; font-weight:900;
  font-size:var(--bk-fs-md); letter-spacing:.3px; cursor:pointer; position:relative; overflow:hidden;
  box-shadow:0 8px 24px rgba(255,107,53,.45), inset 0 1px 0 rgba(255,255,255,.2);
  transition:transform .15s, box-shadow .15s;
}
.btn-next-step::before, .btn-submit::before {
  content:''; position:absolute; top:0; left:-100%; width:60%; height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.25),transparent);
  animation:shimmer 2.8s ease-in-out infinite;
}
@keyframes shimmer { 0%{left:-100%} 60%,100%{left:160%} }
.btn-next-step:active, .btn-submit:active { transform:scale(.97); }
.btn-next-step:hover, .btn-submit:hover { box-shadow:0 12px 32px rgba(255,107,53,.6); }
.btn-next-step:disabled, .btn-submit:disabled { opacity:.6; cursor:not-allowed; transform:none; }

.btn-spinner {
  display:none; width:18px; height:18px;
  border:2.5px solid rgba(255,255,255,.35); border-top-color:#fff;
  border-radius:50%; animation:spin .7s linear infinite;
  vertical-align:middle; margin-right:.4rem;
}
@keyframes spin { to{transform:rotate(360deg);} }
.btn-submit.loading .btn-spinner { display:inline-block; }
.btn-submit.loading .btn-text { opacity:.6; }

/* alert */
.alert {
  border-radius:12px; padding:.7rem .9rem; margin-bottom:.9rem;
  font-size:var(--bk-fs-xs); font-weight:700; display:flex; align-items:center; gap:.5rem;
}
.alert-error { background:rgba(255,80,80,.18); border:1px solid rgba(255,80,80,.35); color:#ffaaaa; }
.alert-success { background:rgba(0,196,140,.18); border:1px solid rgba(0,196,140,.35); color:#7fffd4; }

/* login link */
.login-row {
  text-align:center; margin-top:.9rem;
  font-size:var(--bk-fs-xs); font-weight:700; color:rgba(255,255,255,.65);
}
.login-row a {
  color:rgba(144,224,239,.95); text-decoration:none; font-weight:900; transition:color .2s;
}
.login-row a:hover { color:#fff; }

/* success state */
#success-state {
  display:none; text-align:center; padding:1rem 0;
}
#success-state .success-icon { display:grid; place-items:center; margin-bottom:.8rem; color:#ffd9e2; }
#success-state .success-icon svg { width:3.2rem; height:3.2rem; }
#success-state .success-title {
  font-weight:800; font-size:1.4rem; color:#fff; margin-bottom:.4rem;
}
#success-state .success-sub {
  font-size:var(--bk-fs-xs); font-weight:700; color:rgba(255,255,255,.75);
  line-height:1.6; margin-bottom:1.2rem;
}
.btn-to-login {
  display:inline-flex; align-items:center; gap:.4rem;
  background:linear-gradient(135deg,#047857 0%,#04805f 100%);
  color:#fff; font-family:'Plus Jakarta Sans',sans-serif; font-weight:900;
  font-size:var(--bk-fs-md); border:none; border-radius:99px;
  padding:.82rem 2rem; cursor:pointer; text-decoration:none;
  box-shadow:0 8px 24px rgba(0,196,140,.45);
  transition:transform .15s, box-shadow .15s;
}
.btn-to-login:hover { box-shadow:0 12px 32px rgba(0,196,140,.6); transform:scale(1.02); }

/* footer */
#footer {
  margin-top:1.4rem; text-align:center; display:inline-block; padding:.3rem .9rem; border-radius:99px; background:rgba(35,4,16,.45);
  font-size:var(--bk-fs-2xs); font-weight:700; color:rgba(255,255,255,.72); letter-spacing:.3px;
}

/* back button fixed */
#btn-back {
  position:fixed; top:max(1rem, env(safe-area-inset-top)); left:1rem; z-index:10;
  width:38px; height:38px; border-radius:50%; border:none; cursor:pointer;
  background:rgba(255,255,255,.15); -webkit-backdrop-filter:blur(8px); backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.25);
  display:flex; align-items:center; justify-content:center;
  color:#fff; font-size:1.1rem; transition:background .2s, transform .15s;
  text-decoration:none;
}
#btn-back:hover { background:rgba(255,255,255,.25); }
#btn-back:active { transform:scale(.92); }
</style>
<style>
#header{opacity:1!important} #card{opacity:1!important;transform:none!important}
#brand-logo{animation:brand-intro .7s ease-out both}
.ambient-flower{position:absolute;color:rgba(255,255,255,.35);font-size:28px;animation:flower-float 5s ease-in-out infinite alternate}
@keyframes brand-intro{from{transform:rotate(-120deg) scale(.85)}to{transform:rotate(0) scale(1)}}
@keyframes flower-float{from{transform:translateY(0) rotate(0)}to{transform:translateY(-14px) rotate(20deg)}}
#registration-progress{font-size:var(--bk-fs-2xs);color:#fff;text-align:center;margin-bottom:12px;min-height:1em}
html:not(.register-enhanced) #registration-progress{display:none}
#btn-back,.btn-back-step,.btn-next-step,.btn-submit,.pw-toggle{min-height:44px;min-width:44px}
:focus-visible{outline:3px solid #fff;outline-offset:3px}
@media(prefers-reduced-motion:reduce){#brand-logo,.ambient-flower,#drum-spin-ring,#drum-wrap::after,.btn-next-step::before,.btn-submit::before{animation:none!important}}
</style>
<link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}?v={{ filemtime(public_path('css/page-transitions.css')) }}"><script src="{{ asset('js/page-transitions.js') }}?v={{ filemtime(public_path('js/page-transitions.js')) }}" defer></script>
@include('layouts.component._theme_head')
</head>
<body class="auth-page">
@php
$oldText = function (string $key, $fallback = '') {
    $value = old($key, $fallback);
    return is_scalar($value) ? (string) $value : '';
};
@endphp

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

<a href="{{ route('login') }}" id="btn-back" title="Kembali ke masuk" aria-label="Kembali ke masuk">&#8592;</a>

<div id="page">
  <div id="header">
    <div id="drum-wrap">
      <div id="drum-spin-ring"></div>
      <img id="brand-logo" src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt="Logo Berkah Kreatif" style="width:100%;height:100%;object-fit:contain;border-radius:50%;background:white">
    </div>
    <div class="wm-name">Berkah Kreatif</div>
    <div class="wm-sub">PAPAN BUNGA&nbsp;&nbsp;•&nbsp;&nbsp;BERKAH KREATIF</div>
    <div class="wm-bar"></div>
  </div>

  <div id="card" role="main">
    <h1 class="card-title">Buat Akun Baru</h1>
    <div class="card-sub">Daftar sebagai pelanggan Berkah Kreatif</div>

    @if(session('error'))
      <div class="alert alert-error">@include('layouts.component._icon', ['name' => 'alert-triangle', 'size' => 20])<span>{{ session('error') }}</span></div>
    @endif

    <!-- Step Indicator -->
    <div class="step-indicator" id="step-indicator" aria-hidden="true">
      <div class="step-dot active" id="sdot-1">1</div>
      <div class="step-line" id="sline-1"></div>
      <div class="step-dot" id="sdot-2">2</div>
      <div class="step-line" id="sline-2"></div>
      <div class="step-dot" id="sdot-3">3</div>
    </div>

    @if($errors->any())<div class="alert alert-error" role="alert">Periksa kembali isian yang ditandai. Data yang sudah diisi tetap tersimpan, kecuali kata sandi.</div>@endif
    <p id="registration-progress" class="registration-progress" role="status" aria-live="polite"></p>
    <form method="POST" action="{{ route('register.post') }}" id="register-form" novalidate>
      @csrf

      <!-- ══ STEP 1: Data Diri ══ -->
      <div class="form-step active" id="step-1">
        <div class="form-group">
          <label class="form-label" for="name">Nama Lengkap</label>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'user', 'size' => 20])</span>
            <input class="form-input @error('name') error @enderror"
              type="text" id="name" name="name" aria-describedby="err-name"
              placeholder="Nama sesuai identitas"
              value="{{ $oldText('name') }}" autocomplete="name">
          </div>
          @error('name')<div class="form-error show">{{ $message }}</div>@enderror
          <div class="form-error" aria-live="polite" id="err-name"></div>
        </div>

        <div class="form-group">
          <div class="form-label" id="gender-label">Jenis Kelamin</div>
          <div class="gender-row" role="radiogroup" aria-labelledby="gender-label" aria-describedby="err-gender">
            <input type="radio" class="gender-opt" name="gender" id="g-male" value="L" {{ $oldText('gender')=='L' ? 'checked' : '' }}>
            <label class="gender-label" for="g-male">
              <span>Laki-laki</span>
            </label>
            <input type="radio" class="gender-opt" name="gender" id="g-female" value="P" {{ $oldText('gender')=='P' ? 'checked' : '' }}>
            <label class="gender-label" for="g-female">
              <span>Perempuan</span>
            </label>
          </div>
          @error('gender')<div class="form-error show">{{ $message }}</div>@enderror
          <div class="form-error" aria-live="polite" id="err-gender"></div>
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Nomor HP (WhatsApp)</label>
          <div class="phone-row">
            <div class="phone-prefix">🇮🇩 +62</div>
            <div class="phone-input-wrap">
              <input class="form-input @error('phone') error @enderror"
                type="tel" id="phone" name="phone" aria-describedby="err-phone"
                placeholder="8xxxxxxxxxx"
                value="{{ $oldText('phone') }}" inputmode="tel" autocomplete="tel">
            </div>
          </div>
          @error('phone')<div class="form-error show">{{ $message }}</div>@enderror
          <div class="form-error" aria-live="polite" id="err-phone"></div>
        </div>

        <div class="btn-row">
          <button type="button" class="btn-next-step" id="btn-step1" onclick="goStep(2)">
            Lanjut &rarr;
          </button>
        </div>
      </div>

      <!-- ══ STEP 2: Akun & Kata sandi ══ -->
      <div class="form-step" id="step-2">
        <div class="form-group">
          <label class="form-label" for="email">
            Email <span style="font-weight:700;color:rgba(255,255,255,.72);text-transform:none;letter-spacing:0;">(opsional)</span>
          </label>
          <div class="input-wrap">
            <span class="input-icon">@</span>
            <input class="form-input @error('email') error @enderror"
              type="email" id="email" name="email" aria-describedby="err-email"
              placeholder="Boleh dikosongkan"
              value="{{ $oldText('email') }}" autocomplete="email" inputmode="email">
          </div>
          @error('email')<div class="form-error show">{{ $message }}</div>@enderror
          <div class="form-error" aria-live="polite" id="err-email"></div>
          <div style="font-size:var(--bk-fs-2xs);font-weight:700;color:rgba(255,255,255,.72);margin-top:.35rem;line-height:1.4;">
            Nomor HP digunakan untuk kontak. Notifikasi pesanan tersedia di dalam aplikasi.
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Kata sandi</label>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'lock', 'size' => 20])</span>
            <input class="form-input" type="password" id="password" name="password" aria-describedby="err-pw"
              placeholder="Min. 8 karakter" autocomplete="new-password">
            <button type="button" class="pw-toggle" id="pw-toggle-1" aria-label="Tampilkan kata sandi" aria-pressed="false"><span data-glyph="eye">@include('layouts.component._icon', ['name' => 'eye', 'size' => 20])</span><span data-glyph="eye-off" hidden>@include('layouts.component._icon', ['name' => 'eye-off', 'size' => 20])</span></button>
          </div>
          @error('password')<div class="form-error show">{{ $message }}</div>@enderror
          <div class="form-error" aria-live="polite" id="err-pw"></div>
          <!-- Strength meter -->
          <div class="pw-strength" id="pw-strength">
            <div class="pw-strength-bar">
              <div class="pw-strength-fill" id="pw-strength-fill"></div>
            </div>
            <div class="pw-strength-lbl" id="pw-strength-lbl">Ketik kata sandi...</div>
            <p style="font-size:var(--bk-fs-2xs);color:#fff;margin-top:.4rem">Selain minimal 8 karakter, kombinasi berikut disarankan, bukan wajib.</p><ul class="pw-hints">
              <li class="pw-hint" id="hint-len">Min. 8 karakter</li>
              <li class="pw-hint" id="hint-upper">Huruf besar</li>
              <li class="pw-hint" id="hint-num">Angka</li>
              <li class="pw-hint" id="hint-sym">Simbol (!@#...)</li>
            </ul>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirmation">Konfirmasi kata sandi</label>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'lock-keyhole', 'size' => 20])</span>
            <input class="form-input" type="password" id="password_confirmation" name="password_confirmation" aria-describedby="err-pwc"
              placeholder="Ulangi kata sandi" autocomplete="new-password">
            <button type="button" class="pw-toggle" id="pw-toggle-2" aria-label="Tampilkan konfirmasi kata sandi" aria-pressed="false"><span data-glyph="eye">@include('layouts.component._icon', ['name' => 'eye', 'size' => 20])</span><span data-glyph="eye-off" hidden>@include('layouts.component._icon', ['name' => 'eye-off', 'size' => 20])</span></button>
          </div>
          <div class="form-error" aria-live="polite" id="err-pwc"></div>
        </div>

        <div class="btn-row">
          <button type="button" class="btn-back-step" aria-label="Kembali ke data diri" onclick="goStep(1)">&#8592;</button>
          <button type="button" class="btn-next-step" onclick="goStep(3)">Lanjut &rarr;</button>
        </div>
      </div>

      <!-- ══ STEP 3: Alamat & Konfirmasi ══ -->
      <div class="form-step" id="step-3">
        <div class="form-group">
          <label class="form-label" for="address">Alamat Lengkap</label>
          <div class="input-wrap">
            <span class="input-icon" style="top:14px;transform:none;" aria-hidden="true">@include('layouts.component._icon', ['name' => 'home', 'size' => 20])</span>
            <textarea class="form-input" id="address" name="address" aria-describedby="err-address"
              placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan..."
              rows="3" style="padding-left:2.65rem;resize:none;">{{ $oldText('address') }}</textarea>
          </div>
          @error('address')<div class="form-error show">{{ $message }}</div>@enderror
          <div class="form-error" aria-live="polite" id="err-address"></div>
        </div>

        <div class="form-group">
          <label class="form-label" for="address_note">Catatan Alamat (Opsional)</label>
          <div class="input-wrap">
            <span class="input-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'file-text', 'size' => 20])</span>
            <input class="form-input" type="text" id="address_note" name="address_note"
              placeholder="Warna pagar, patokan, dll."
              value="{{ $oldText('address_note') }}">
          </div>
        </div>

        <div class="terms-row">
          <input type="checkbox" class="terms-cb" id="terms" name="terms" aria-describedby="err-terms" value="1" @checked($oldText('terms')) required>
          <label class="terms-txt" for="terms">
            Saya menyetujui penggunaan data saya untuk pengelolaan akun dan pesanan Berkah Kreatif.
          </label>
        </div>
        @error('terms')<div class="form-error show">{{ $message }}</div>@enderror
        <div class="form-error" aria-live="polite" id="err-terms"></div>

        <div class="btn-row">
          <button type="button" class="btn-back-step" aria-label="Kembali ke akun dan kata sandi" onclick="goStep(2)">&#8592;</button>
          <button type="submit" class="btn-submit bk-auth" id="btn-submit">
            <span class="btn-spinner"></span>
            <span class="bk-auth__bg" aria-hidden="true"></span>
            <svg class="bk-auth__splash" viewBox="0 0 342 208" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g stroke="currentColor" stroke-width="3" stroke-linecap="round">
                    <path d="M48.5 102.3C48.5 102.3 36.2 92.5 24.8 98.7C13.4 104.9 3 98.7 3 98.7"></path>
                    <path d="M290.5 102.3C290.5 102.3 302.8 92.5 314.2 98.7C325.6 104.9 339 96.2 339 96.2"></path>
                    <path d="M278.5 68.2C278.5 68.2 284.3 54.1 298.7 52.8C313.1 51.5 315.5 42.3 315.5 42.3" stroke-opacity=".4"></path>
                    <path d="M278.5 141.7C278.5 141.7 284.3 155.8 298.7 157.1C313.1 158.4 315.5 167.6 315.5 167.6" stroke-opacity=".4"></path>
                    <path d="M225.3 60.8C225.3 60.8 221.5 45.2 231.2 35.6C240.9 26 239.8 19.5 239.8 19.5"></path>
                    <path d="M225.3 153.2C225.3 153.2 221.5 168.8 231.2 178.4C240.9 188 239.8 194.5 239.8 194.5"></path>
                </g>
            </svg>
            <span class="bk-auth__wrap" aria-hidden="true">
              <svg class="bk-auth__path" viewBox="0 0 221 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M182.674 2H203C211.837 2 219 9.16344 219 18V24C219 32.8366 211.837 40 203 40H18C9.16345 40 2 32.8366 2 24V18C2 9.16344 9.16344 2 18 2H47.8855" stroke="#ffd9e3" stroke-width="3" stroke-linecap="round"></path>
              </svg>
              <span class="bk-auth__outline"></span>
              <span class="bk-auth__content">
                <span class="bk-auth__icon"><div></div></span>
                <span class="bk-auth__text"><span class="bk-auth__chars bk-auth__chars--a"><span style="--i: 1">D</span><span style="--i: 2">a</span><span style="--i: 3">f</span><span style="--i: 4">t</span><span style="--i: 5">a</span><span style="--i: 6">r</span></span><span class="bk-auth__chars bk-auth__chars--b"><span style="--i: 1">B</span><span style="--i: 2">u</span><span style="--i: 3">a</span><span style="--i: 4">t</span><span style="--i: 5"> </span><span style="--i: 6">A</span><span style="--i: 7">k</span><span style="--i: 8">u</span><span style="--i: 9">n</span></span></span>
              </span>
            </span>
            <span class="btn-text bk-sr-only">Daftar Sekarang</span>
          </button>
        </div>
      </div>

    </form>

    <!-- Success State (shown after successful registration) -->
    <div id="success-state">
      @if(session('registered'))
      <div class="success-icon" aria-hidden="true">@include('layouts.component._icon', ['name' => 'party-popper', 'size' => 32])</div>
      <div class="success-title">Akun Berhasil Dibuat!</div>
      <div class="success-sub">
        Selamat datang di SIMALUN!<br>
        Akun kamu sudah aktif. Silakan masuk untuk mulai memesan.
      </div>
      <a href="{{ route('login') }}" class="btn-to-login">Masuk Sekarang &rarr;</a>
      @endif
    </div>

    <div class="login-row">
      Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
    </div>
  </div>

  <div id="footer">
    &copy; {{ date('Y') }} Berkah Kreatif · Papan Bunga
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ── SHOW SUCCESS STATE IF SESSION ── */
  @if(session('registered'))
    document.getElementById('step-indicator').style.display = 'none';
    document.querySelectorAll('.form-step,.login-row').forEach(el => el.style.display='none');
    const ss = document.getElementById('success-state');
    ss.style.display = 'block';

  @endif

  /* ── STEP NAVIGATION ── */
  @php
    $initialStep = $errors->hasAny(['name', 'gender', 'phone']) ? 1 : ($errors->hasAny(['email', 'password']) ? 2 : ($errors->hasAny(['address', 'address_note', 'terms']) ? 3 : 1));
  @endphp
  let currentStep = {{ $initialStep }};
  document.querySelectorAll('.form-step').forEach((step) => step.classList.remove('active'));
  document.getElementById(`step-${currentStep}`).classList.add('active');
  updateStepDots(currentStep);
  window.goStep = function(next) {
    // Hanya validasi saat maju ke step berikutnya. Saat mundur (kembali),
    // biarkan user kembali tanpa harus mengisi step saat ini dulu.
    if (next > currentStep && !validateStep(currentStep)) return;
    const prev = currentStep;
    currentStep = next;

    document.getElementById(`step-${prev}`).classList.remove('active');
    document.getElementById(`step-${next}`).classList.add('active');
    updateStepDots(next);
    document.querySelector(`#step-${next} input:not([type=hidden]), #step-${next} textarea`)?.focus();
  };

  function updateStepDots(step) {
    document.getElementById('registration-progress').textContent = `Langkah ${step} dari 3: ${['Data diri', 'Akun dan kata sandi', 'Alamat dan persetujuan'][step - 1]}`;
    for (let i = 1; i <= 3; i++) {
      const dot = document.getElementById(`sdot-${i}`);
      dot.classList.remove('active','done');
      // Nomor langkah tetap ditulis; tanda selesai digambar CSS, bukan emoji.
      if (i < step) dot.classList.add('done');
      else if (i === step) dot.classList.add('active');
      dot.textContent = i;
    }
    for (let i = 1; i <= 2; i++) {
      document.getElementById(`sline-${i}`).classList.toggle('done', i < step);
    }
  }

  /* ── STEP VALIDATION ── */
  function validateStep(step) {
    let ok = true;
    if (step === 1) {
      const name = document.getElementById('name').value.trim();
      const gender = document.querySelector('input[name="gender"]:checked');
      const phone = document.getElementById('phone').value.trim();

      clearErr('err-name'); clearErr('err-gender'); clearErr('err-phone');
      document.getElementById('name').classList.remove('error');
      document.getElementById('phone').classList.remove('error');

      if (!name || name.length < 3) { showErr('err-name','Nama minimal 3 karakter'); document.getElementById('name').classList.add('error'); ok=false; }
      if (!gender) { showErr('err-gender','Pilih jenis kelamin'); ok=false; }
      if (!phone || !/^8[0-9]{8,12}$/.test(phone.replace(/[\s-]/g,''))) {
        showErr('err-phone','Nomor HP tidak valid (contoh: 81234567890)');
        document.getElementById('phone').classList.add('error'); ok=false;
      }
    } else if (step === 2) {
      const email = document.getElementById('email').value.trim();
      const pw = document.getElementById('password').value;
      const pwc = document.getElementById('password_confirmation').value;

      clearErr('err-email'); clearErr('err-pw'); clearErr('err-pwc');
      document.getElementById('email').classList.remove('error');
      document.getElementById('password').classList.remove('error');
      document.getElementById('password_confirmation').classList.remove('error');

      // Email opsional — hanya validasi format jika diisi
      if (email && !document.getElementById('email').validity.valid) {
        showErr('err-email','Format email tidak valid'); document.getElementById('email').classList.add('error'); ok=false;
      }
      if (!pw || pw.length < 8) {
        showErr('err-pw','Kata sandi minimal 8 karakter'); document.getElementById('password').classList.add('error'); ok=false;
      }
      if (pw !== pwc) {
        showErr('err-pwc','Konfirmasi password tidak cocok'); document.getElementById('password_confirmation').classList.add('error'); ok=false;
      }
    }
    if (!ok) {
      const firstError = document.querySelector(`#step-${step} .form-error.show[id^="err-"]`);
      const fields = { 'err-name': 'name', 'err-gender': 'g-male', 'err-phone': 'phone', 'err-email': 'email', 'err-pw': 'password', 'err-pwc': 'password_confirmation' };
      document.getElementById(fields[firstError?.id])?.focus();
    }
    return ok;
  }

  function showErr(id, msg) { const el=document.getElementById(id); if(el){el.textContent=msg;el.classList.add('show');} }
  function clearErr(id) { const el=document.getElementById(id); if(el){el.textContent='';el.classList.remove('show');} }

  /* ── STEP 3 SUBMIT VALIDATION ── */
  document.getElementById('register-form').addEventListener('submit', function(e) {
    if (document.documentElement.classList.contains('register-enhanced') && currentStep < 3) { e.preventDefault(); window.goStep(currentStep + 1); return; }
    const addr = document.getElementById('address').value.trim();
    const terms = document.getElementById('terms').checked;
    clearErr('err-address'); clearErr('err-terms');
    let ok = true;
    if (!addr || addr.length < 10) { showErr('err-address','Alamat minimal 10 karakter'); ok=false; }
    if (!terms) { showErr('err-terms','Kamu harus menyetujui syarat & ketentuan'); ok=false; }
    if (!ok) { e.preventDefault(); document.getElementById(addr.length < 10 ? 'address' : 'terms').focus(); return; }
    document.getElementById('btn-submit').classList.add('loading');
    document.getElementById('btn-submit').disabled = true;
  });

  window.addEventListener('pageshow', () => { const btn = document.getElementById('btn-submit'); btn.disabled = false; btn.classList.remove('loading'); });

  /* ── PASSWORD TOGGLE ── */
  function togglePw(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    btn.addEventListener('click', function() {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      this.querySelector('[data-glyph="eye"]').hidden = show;
    this.querySelector('[data-glyph="eye-off"]').hidden = !show;
      this.setAttribute('aria-pressed', String(show));
      this.setAttribute('aria-label', (show ? 'Sembunyikan ' : 'Tampilkan ') + (inputId === 'password_confirmation' ? 'konfirmasi kata sandi' : 'kata sandi'));
    });
  }
  togglePw('password','pw-toggle-1');
  togglePw('password_confirmation','pw-toggle-2');

  /* ── PASSWORD STRENGTH ── */
  const pwInput = document.getElementById('password');
  pwInput.addEventListener('input', function() {
    const v = this.value;
    const strength = document.getElementById('pw-strength');
    const fill = document.getElementById('pw-strength-fill');
    const lbl = document.getElementById('pw-strength-lbl');
    if (!v) { strength.classList.remove('show'); return; }
    strength.classList.add('show');

    const hasLen = v.length >= 8;
    const hasUpper = /[A-Z]/.test(v);
    const hasNum = /[0-9]/.test(v);
    const hasSym = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(v);

    document.getElementById('hint-len').classList.toggle('ok', hasLen);
    document.getElementById('hint-upper').classList.toggle('ok', hasUpper);
    document.getElementById('hint-num').classList.toggle('ok', hasNum);
    document.getElementById('hint-sym').classList.toggle('ok', hasSym);

    const score = [hasLen, hasUpper, hasNum, hasSym].filter(Boolean).length;
    const configs = [
      {w:'15%', bg:'#ffa8a8', txt:'Sangat Lemah'},
      {w:'35%', bg:'#ffb37a', txt:'Lemah'},
      {w:'60%', bg:'#ffd166', txt:'Cukup'},
      {w:'80%', bg:'#8ce6a8', txt:'Kuat'},
      {w:'100%',bg:'#5ce8bd', txt:'Sangat Kuat'},
    ];
    const cfg = configs[score] || configs[0];
    fill.style.width = cfg.w;
    fill.style.background = cfg.bg;
    lbl.textContent = cfg.txt;
    lbl.style.color = cfg.bg;
  });

  /* ── PHONE: normalisasi input agar selalu diawali 8 ── */
  (function () {
    const phone = document.getElementById('phone');

    // Ubah input apa pun jadi format lokal tanpa awalan: 8xxxxxxxxxx
    function normalizePhone(raw) {
      // Buang semua karakter selain angka (termasuk +, spasi, -, dll)
      let d = (raw || '').replace(/\D/g, '');
      // Buang kode negara Indonesia jika di-paste (+62 / 62)
      if (d.startsWith('62')) d = d.slice(2);
      // Buang semua angka 0 di depan (08xx / 0xx)
      d = d.replace(/^0+/, '');
      return d;
    }

    function applyNormalize() {
      const before = phone.value;
      const after = normalizePhone(before);
      if (before !== after) {
        phone.value = after;
        // pertahankan kursor di akhir agar tidak melompat
        phone.setSelectionRange(after.length, after.length);
      }
    }

    phone.addEventListener('input', applyNormalize);

  })();

  document.documentElement.classList.add('register-enhanced');
});
</script>
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>