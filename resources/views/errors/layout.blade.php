@php
    $accent = $accent ?? '#85092d';
    $primary = $primary ?? ['url' => url('/'), 'label' => 'Kembali ke Beranda', 'icon' => '🏠'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex">
<title>{{ $code }} – {{ $title }} | Berkah Kreatif</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',sans-serif;color:#301722;background:linear-gradient(168deg,#54051f 0%,#85092d 55%,#b6435e 100%);min-height:100vh;min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:24px;overflow:hidden}
body::before,body::after{content:'';position:absolute;border-radius:50%;background:rgba(255,255,255,.09);pointer-events:none}
body::before{width:280px;height:280px;top:-80px;right:-80px}
body::after{width:200px;height:200px;bottom:-60px;left:-60px}
main{width:100%;max-width:440px;position:relative;z-index:2}
.card{background:#fffaf8;border-radius:28px;padding:34px 26px 30px;box-shadow:0 24px 60px rgba(45,3,20,.4);text-align:center;border:1.5px solid rgba(255,255,255,.5)}
.logo{width:54px;height:54px;object-fit:contain;border-radius:16px;background:#fff;padding:4px;box-shadow:0 6px 18px rgba(84,5,31,.14);margin-bottom:14px}
.code-pill{display:inline-block;background:{{ $accent }}1a;color:{{ $accent }};font-weight:900;font-size:.72rem;letter-spacing:1.4px;padding:6px 14px;border-radius:999px;text-transform:uppercase;margin-bottom:14px}
.hero-emoji{font-size:3.6rem;margin:2px 0 10px;display:inline-block;animation:wobble 4.5s ease-in-out infinite}
@keyframes wobble{0%,100%{transform:rotate(-4deg) translateY(0)}50%{transform:rotate(4deg) translateY(-6px)}}
.code-big{font-weight:800;font-size:clamp(3rem,12vw,4.2rem);color:{{ $accent }};letter-spacing:-1px;margin-bottom:4px;line-height:1.1}
h1{font-weight:800;font-size:clamp(1.28rem,5vw,1.6rem);color:#54051f;margin-bottom:10px;line-height:1.3}
p.lead{font-size:.94rem;font-weight:600;color:#5b4149;line-height:1.6;margin-bottom:22px;max-width:34ch;margin-left:auto;margin-right:auto;overflow-wrap:anywhere}
.actions{display:flex;flex-direction:column;gap:10px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:13px 22px;border-radius:999px;font-weight:800;font-size:.92rem;text-decoration:none;border:0;cursor:pointer;transition:transform .15s ease}
.btn:focus-visible{outline:3px solid #bc5b75;outline-offset:3px}
.btn:active{transform:scale(.97)}
.btn-primary{background:linear-gradient(135deg,#85092d 0%,#b6435e 100%);color:#fff;box-shadow:0 8px 22px rgba(133,9,45,.32)}
.btn-secondary{background:#fff;color:#75102e;border:1.5px solid #e5cbd0}
.tip{margin-top:20px;padding-top:16px;border-top:1.5px dashed #e8d6dc;font-size:.78rem;font-weight:700;color:#7a626a;line-height:1.55}
.brand{margin-top:16px;display:flex;align-items:center;justify-content:center;gap:8px;font-size:.64rem;font-weight:900;color:rgba(255,255,255,.9);letter-spacing:2.4px;text-transform:uppercase}
.brand span{display:inline-block}
/* Animasi masuk: kartu muncul, isi menyusul berurutan. Murni transform +
   opacity agar tidak memicu tata letak ulang; dimatikan saat gerak dikurangi. */
.card{animation:errIn .52s cubic-bezier(.22,.7,.25,1) both}
.code-pill,.code-big,h1,p.lead,.actions,.tip{animation:errRise .5s cubic-bezier(.22,.7,.25,1) both}
.code-pill{animation-delay:.05s}.code-big{animation-delay:.09s}h1{animation-delay:.13s}
p.lead{animation-delay:.17s}.actions{animation-delay:.21s}.tip{animation-delay:.25s}
.hero-emoji{animation:errRise .5s cubic-bezier(.22,.7,.25,1) .07s both,wobble 4.5s ease-in-out .6s infinite}
@keyframes errIn{from{opacity:0;transform:translateY(16px) scale(.98)}}
@keyframes errRise{from{opacity:0;transform:translateY(10px)}}
@media(max-width:360px){.card{padding:28px 18px 24px}.hero-emoji{font-size:3rem}}
@media(prefers-reduced-motion:reduce){.hero-emoji{animation:none}.card,.code-pill,.code-big,h1,p.lead,.actions,.tip{animation:none}*,*::before,*::after{transition:none!important}}
</style>
@include('layouts.component._theme_head')
</head>
<body>
<main>
    <div class="card">
        <img class="logo" src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt="Berkah Kreatif" width="54" height="54">
        <span class="code-pill">Error {{ $code }}</span>
        <div class="hero-emoji" aria-hidden="true">{{ $emoji }}</div>
        <div class="code-big">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p class="lead">{{ $message }}</p>
        <div class="actions">
            <a href="{{ $primary['url'] }}" class="btn btn-primary">{!! $primary['icon'] ?? '🏠' !!} {{ $primary['label'] }}</a>
            @isset($secondary)<a href="{{ $secondary['url'] }}" class="btn btn-secondary">{!! $secondary['icon'] ?? '↩️' !!} {{ $secondary['label'] }}</a>@endisset
        </div>
        @isset($tip)<div class="tip">{{ $tip }}</div>@endisset
    </div>
    <div class="brand"><span>Berkah Kreatif</span> &bull; <span>SIMALUN</span></div>
</main>
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>
