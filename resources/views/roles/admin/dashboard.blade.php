<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Dashboard Admin – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
{{-- Canonical Design System tokens (single source of truth). Standalone page:
     not compiled through Vite, so include tokens directly in <head>. --}}
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN DASHBOARD
   Catatan: nama CSS variable di :root dipakai juga oleh
   _analytics_section.blade.php — jangan di-rename.
═══════════════════════════════════════════════════════════ */
:root {
    --blue:       #0077b6;
    --blue-mid:   #0091cf;
    --blue-dark:  #002f5c;
    --blue-lt:    #e8f4fd;
    --sky:        #48cae4;
    --teal:       #0d9488;
    --teal-lt:    #ccfbf1;
    --orange:     #FF6B35;
    --amber:      #f59e0b;
    --orange-lt:  #fff1ea;
    --green:      #059669;
    --green-lt:   #e7faf2;
    --red:        #e23b3b;
    --red-lt:     #fff1f1;
    --violet:     #6366f1;
    --violet-lt:  #eef0ff;
    --surface:    #eef4fa;
    --card:       #ffffff;
    --ink:        #112030;
    --ink-mid:    #46586c;
    --ink-lt:     #8b9bad;
    --border:     #e4ecf4;
    --line-soft:  #f0f5fa;
    --radius:     var(--radius-card); /* canonical 16px (reconciled from 20px) */
    --radius-sm:  var(--radius-btn);  /* canonical 12px (reconciled from 14px) */
    --shadow:     0 2px 14px rgba(0,47,92,0.05);
    --shadow-md:  0 10px 30px rgba(0,47,92,0.10);
    --font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html { scroll-behavior: smooth; }
body {
    font-family: var(--font);
    background: var(--surface);
    color: var(--ink);
    min-height: 100vh;
    padding-bottom: calc(74px + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}

/* ── Entrance animation (pure CSS, no GSAP) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: none; } }
.reveal, .js-in { opacity: 0; animation: riseIn 0.5s cubic-bezier(0.22,0.61,0.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}
.d4{animation-delay:.22s}.d5{animation-delay:.28s}.d6{animation-delay:.34s}
@media (prefers-reduced-motion: reduce){ .reveal,.js-in{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════
   HERO
═══════════════════════════════════════════════ */
.hero {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(135% 110% at 88% -25%, rgba(72,202,228,0.55) 0%, transparent 48%),
        linear-gradient(140deg, #0077b6 0%, #0090cd 52%, #00a6d6 100%);
    padding-top: max(env(safe-area-inset-top, 0px), 12px);
    padding-bottom: 46px;
}
.hero__bubble {
    position: absolute; border-radius: 50%;
    background: radial-gradient(circle at 32% 28%, rgba(255,255,255,0.32), rgba(255,255,255,0.01));
    border: 1px solid rgba(255,255,255,0.12);
    pointer-events: none;
}
.hero__wave { position: absolute; left: -2%; right: -2%; bottom: -1px; width: 104%; pointer-events: none; }
.hero__inner { position: relative; z-index: 2; max-width: 520px; margin: 0 auto; padding: 8px 18px 0; }

/* App bar */
.appbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
.brand { display: flex; align-items: center; gap: 9px; }
.brand__logo {
    width: 36px; height: 36px; border-radius: 11px;
    background: linear-gradient(155deg, rgba(255,255,255,0.32), rgba(255,255,255,0.1));
    border: 1px solid rgba(255,255,255,0.35);
    display: flex; align-items: center; justify-content: center;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.3);
}
.brand__logo svg { width: 21px; height: 21px; }
.brand__name { display: block; font-size: 0.95rem; font-weight: 800; color: #fff; line-height: 1; letter-spacing: 0.2px; }
.brand__tag { display: block; font-size: 0.5rem; font-weight: 800; color: rgba(255,255,255,0.72); letter-spacing: 2.5px; text-transform: uppercase; margin-top: 3px; }
.notif-btn {
    width: 38px; height: 38px; border-radius: 11px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.24);
    display: flex; align-items: center; justify-content: center;
    text-decoration: none; position: relative; transition: background 0.2s;
}
.notif-btn:active { background: rgba(255,255,255,0.28); }
.notif-btn svg { width: 19px; height: 19px; stroke: #fff; }
.notif-badge {
    position: absolute; top: 5px; right: 5px;
    background: var(--orange); color: #fff; font-size: 0.52rem; font-weight: 900;
    min-width: 16px; height: 16px; border-radius: 99px;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #0086c4; padding: 0 3px;
}

/* Greeting */
.greet { display: flex; align-items: center; gap: 13px; }
.greet__avatar {
    width: 50px; height: 50px; border-radius: 15px;
    background: rgba(255,255,255,0.22); border: 1.5px solid rgba(255,255,255,0.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; font-weight: 800; color: #fff; flex-shrink: 0;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
}
.greet__text { min-width: 0; flex: 1; }
.greet__hi { font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,0.78); letter-spacing: 0.2px; }
.greet__name { font-size: 1.4rem; font-weight: 800; color: #fff; line-height: 1.12; margin-top: 1px; text-shadow: 0 1px 10px rgba(0,0,0,0.12); }
.greet__date { display: inline-flex; align-items: center; gap: 5px; font-size: 0.68rem; font-weight: 700; color: rgba(255,255,255,0.82); margin-top: 4px; }
.greet__date svg { width: 12px; height: 12px; }

/* Operational summary pill */
.pulse {
    display: flex; align-items: center; gap: 10px;
    margin-top: 16px; padding: 11px 13px;
    background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22);
    border-radius: 14px; text-decoration: none;
    -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px);
    transition: background 0.18s;
}
.pulse:active { background: rgba(255,255,255,0.24); }
.pulse__dot { width: 9px; height: 9px; border-radius: 50%; background: var(--orange); flex-shrink: 0; box-shadow: 0 0 0 0 rgba(255,107,53,0.6); animation: ping 1.8s ease-out infinite; }
.pulse__dot--ok { background: #4ade80; animation: none; }
@keyframes ping { 0%{box-shadow:0 0 0 0 rgba(255,107,53,.55)} 70%{box-shadow:0 0 0 7px rgba(255,107,53,0)} 100%{box-shadow:0 0 0 0 rgba(255,107,53,0)} }
.pulse__txt { flex: 1; font-size: 0.78rem; font-weight: 700; color: #fff; line-height: 1.3; }
.pulse__txt b { font-weight: 800; }
.pulse__arr { stroke: rgba(255,255,255,0.85); width: 17px; height: 17px; flex-shrink: 0; }

/* ═══════════════════════════════════════════════
   STAT TILES (overlap hero)
═══════════════════════════════════════════════ */
.stats {
    position: relative; z-index: 4;
    max-width: 520px; margin: -28px auto 0; padding: 0 16px;
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;
}
.stat {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 16px; padding: 14px 11px;
    box-shadow: var(--shadow-md); text-align: left;
    position: relative; overflow: hidden;
}
.stat__ico { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 9px; }
.stat__ico svg { width: 18px; height: 18px; }
.stat__ico--blue   { background: var(--blue-lt);   color: var(--blue); }
.stat__ico--orange { background: var(--orange-lt); color: var(--orange); }
.stat__ico--green  { background: var(--green-lt);  color: var(--green); }
.stat__val { font-size: 1.6rem; font-weight: 800; color: var(--ink); line-height: 1; }
.stat__lbl { font-size: 0.6rem; font-weight: 700; color: var(--ink-lt); margin-top: 5px; line-height: 1.25; text-transform: uppercase; letter-spacing: 0.3px; }

/* ═══════════════════════════════════════════════
   BODY
═══════════════════════════════════════════════ */
.body { max-width: 520px; margin: 16px auto 0; padding: 0 16px; }

/* Section header */
.section { margin-top: 24px; }
.section__head, .section__header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 13px; padding: 0 2px;
}
.section__title { font-size: 1rem; font-weight: 800; color: var(--ink); letter-spacing: 0.1px; }
.section__title-sm { font-size: 0.7rem; font-weight: 700; color: var(--ink-lt); margin-top: 2px; }
.section__link { display: inline-flex; align-items: center; gap: 2px; font-size: 0.74rem; font-weight: 800; color: var(--blue); text-decoration: none; }
.section__link svg { width: 14px; height: 14px; }

/* Revenue card */
.revenue {
    background: linear-gradient(135deg, #073b63 0%, #0a4d7a 100%);
    border-radius: var(--radius); padding: 17px 18px;
    position: relative; overflow: hidden;
    box-shadow: 0 12px 30px rgba(7,59,99,0.32);
}
.revenue::after { content: ''; position: absolute; width: 150px; height: 150px; border-radius: 50%; background: rgba(72,202,228,0.14); top: -56px; right: -40px; }
.revenue__head { display: flex; align-items: center; gap: 9px; position: relative; z-index: 1; }
.revenue__ico { width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,0.16); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.revenue__ico svg { width: 18px; height: 18px; stroke: #7fe0c4; }
.revenue__cap { font-size: 0.68rem; font-weight: 700; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.5px; }
.revenue__val { font-size: 1.85rem; font-weight: 800; color: #fff; line-height: 1.05; margin-top: 12px; position: relative; z-index: 1; }
.revenue__val span { font-size: 0.95rem; font-weight: 700; color: rgba(255,255,255,0.66); }
.revenue__foot { display: flex; align-items: center; gap: 7px; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.14); position: relative; z-index: 1; }
.revenue__foot svg { width: 15px; height: 15px; stroke: #7fe0c4; flex-shrink: 0; }
.revenue__foot-txt { font-size: 0.74rem; font-weight: 700; color: rgba(255,255,255,0.82); }
.revenue__foot-txt b { color: #fff; font-weight: 800; }

/* ── Priority order cards (horizontal scroll) ── */
.order-scroll { display: flex; gap: 12px; overflow-x: auto; padding: 2px 2px 10px; margin: 0 -2px; scrollbar-width: none; scroll-snap-type: x mandatory; }
.order-scroll::-webkit-scrollbar { display: none; }
.order-card {
    min-width: 284px; width: 284px; scroll-snap-align: start;
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 15px; box-shadow: var(--shadow);
    position: relative; overflow: hidden;
}
.order-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, var(--orange), #ff9a6a); }
.order-card__top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 13px; }
.order-card__user { display: flex; align-items: center; gap: 11px; }
.order-card__avatar { width: 44px; height: 44px; border-radius: 13px; background: linear-gradient(145deg, var(--blue-lt), #d3ecfb); color: var(--blue-dark); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; font-weight: 800; flex-shrink: 0; }
.order-card__name { font-size: 0.9rem; font-weight: 800; color: var(--ink); line-height: 1.1; }
.order-card__code { font-size: 0.66rem; font-weight: 700; color: var(--blue); margin-top: 3px; }
.order-card__badge { font-size: 0.55rem; font-weight: 900; background: var(--orange-lt); color: #d9531e; border: 1px solid #ffd9c7; border-radius: 99px; padding: 4px 9px; text-transform: uppercase; letter-spacing: 0.4px; flex-shrink: 0; }
.order-card__meta { display: flex; flex-direction: column; gap: 7px; }
.order-card__meta-row { display: flex; align-items: center; gap: 8px; font-size: 0.76rem; font-weight: 600; color: var(--ink-mid); }
.order-card__meta-row svg { width: 15px; height: 15px; stroke: var(--ink-lt); flex-shrink: 0; }
.order-card__meta-row span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.order-card__btn { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px; padding: 12px; background: linear-gradient(135deg, var(--blue) 0%, var(--blue-mid) 100%); color: #fff; border-radius: 13px; text-decoration: none; font-size: 0.8rem; font-weight: 800; box-shadow: 0 6px 16px rgba(0,119,182,0.28); transition: transform 0.12s, box-shadow 0.12s; }
.order-card__btn:active { transform: scale(0.97); box-shadow: 0 3px 10px rgba(0,119,182,0.22); }
.order-card__btn svg { width: 17px; height: 17px; }

.order-empty { width: 100%; text-align: center; padding: 32px 20px; background: var(--card); border: 1.5px dashed var(--border); border-radius: var(--radius); }
.order-empty__ico { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 50%; background: var(--green-lt); display: flex; align-items: center; justify-content: center; }
.order-empty__ico svg { width: 27px; height: 27px; stroke: var(--green); }
.order-empty p { color: var(--ink-lt); font-size: 0.82rem; font-weight: 700; line-height: 1.5; }

/* ── Driver row ── */
.driver-scroll { display: flex; gap: 10px; overflow-x: auto; padding: 2px 2px 6px; margin: 0 -2px; scrollbar-width: none; }
.driver-scroll::-webkit-scrollbar { display: none; }
.driver-chip { display: flex; align-items: center; gap: 9px; background: var(--card); border: 1px solid var(--border); border-radius: 99px; padding: 7px 14px 7px 7px; box-shadow: var(--shadow); flex-shrink: 0; }
.driver-chip__av { position: relative; width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(145deg, #e8f4fd, #d3ecfb); color: var(--blue-dark); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 800; flex-shrink: 0; }
.driver-chip__dot { position: absolute; bottom: -1px; right: -1px; width: 11px; height: 11px; border-radius: 50%; background: #22c55e; border: 2px solid var(--card); }
.driver-chip__name { font-size: 0.8rem; font-weight: 800; color: var(--ink); line-height: 1; }
.driver-chip__role { font-size: 0.62rem; font-weight: 700; color: var(--green); margin-top: 3px; }
.driver-empty { width: 100%; text-align: center; padding: 20px; background: var(--card); border: 1.5px dashed var(--border); border-radius: var(--radius-sm); color: var(--ink-lt); font-size: 0.8rem; font-weight: 700; }

/* ── Quick actions ── */
.quick-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.quick-item { display: flex; align-items: center; gap: 12px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; text-decoration: none; color: inherit; box-shadow: var(--shadow); transition: transform 0.12s, box-shadow 0.12s, border-color 0.12s; }
.quick-item:active { transform: scale(0.97); border-color: #cfe0f0; box-shadow: 0 6px 18px rgba(0,47,92,0.08); }
.quick-item__icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.quick-item__icon svg { width: 20px; height: 20px; }
.quick-item__label { font-size: 0.8rem; font-weight: 700; color: var(--ink); line-height: 1.25; }

/* ── Logout ── */
.logout-section { margin-top: 26px; }
.logout-btn { width: 100%; padding: 14px; border-radius: var(--radius-sm); border: 1px solid #fbd4d4; background: #fff5f5; color: #c0271f; font-family: var(--font); font-size: 0.84rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 9px; transition: background 0.15s; }
.logout-btn:active { background: #ffe9e9; }
.logout-btn svg { width: 17px; height: 17px; }
.version-tag { text-align: center; margin: 14px 0 6px; font-size: 0.66rem; font-weight: 700; color: var(--ink-lt); letter-spacing: 0.3px; }
</style>
</head>
<body>

@php
    $fullName  = trim(auth()->user()->name ?? 'Admin');
    $adminName = explode(' ', $fullName)[0] ?: 'Admin';
    $initial   = mb_strtoupper(mb_substr($adminName, 0, 1));
    $jam       = (int) now()->format('H');
    $sapaan    = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
    $tanggal   = \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM YYYY');
    $perluAksi = (int) ($jumlahPrioritas ?? 0);
    $pickupLabels = ['pagi' => 'Pagi · 08–11', 'siang' => 'Siang · 11–15', 'sore' => 'Sore · 15–18'];
@endphp

{{-- ══════════════════════════════════════════════
     HERO
══════════════════════════════════════════════ --}}
<header class="hero">
    <div class="hero__bubble" style="width:120px;height:120px;top:-46px;right:-22px;opacity:.55;"></div>
    <div class="hero__bubble" style="width:42px;height:42px;top:26px;right:128px;opacity:.4;"></div>
    <div class="hero__bubble" style="width:20px;height:20px;top:78px;left:38px;opacity:.3;"></div>

    <div class="hero__inner">
        {{-- App bar --}}
        <div class="appbar">
            <div class="brand">
                <span class="brand__logo" aria-hidden="true">
                    <svg viewBox="0 0 148 148" fill="none">
                        <rect x="22" y="26" width="104" height="96" rx="20" fill="rgba(255,255,255,.18)" stroke="rgba(255,255,255,.55)" stroke-width="3.5"/>
                        <rect x="22" y="26" width="104" height="26" rx="15" fill="rgba(255,255,255,.24)"/>
                        <circle cx="42" cy="39" r="5.5" fill="#FF6B35"/>
                        <circle cx="60" cy="39" r="5.5" fill="#00C48C"/>
                        <circle cx="74" cy="88" r="29" fill="rgba(255,255,255,.16)" stroke="rgba(255,255,255,.7)" stroke-width="3.5"/>
                        <path d="M63 86 Q74 75 85 86 L81 99 H67Z" fill="#fff" opacity=".92"/>
                    </svg>
                </span>
                <div>
                    <span class="brand__name">Azka Laundry</span>
                    <span class="brand__tag">Admin Panel</span>
                </div>
            </div>
            <a href="{{ route('admin.notifications') }}" class="notif-btn" aria-label="Notifikasi">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                @if(($adminUnread ?? 0) > 0)
                    <span class="notif-badge">{{ $adminUnread > 9 ? '9+' : $adminUnread }}</span>
                @endif
            </a>
        </div>

        {{-- Greeting --}}
        <div class="greet">
            <div class="greet__avatar" aria-hidden="true">{{ $initial }}</div>
            <div class="greet__text">
                <div class="greet__hi">{{ $sapaan }},</div>
                <h1 class="greet__name">{{ $adminName }}</h1>
                <span class="greet__date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    {{ $tanggal }}
                </span>
            </div>
        </div>

        {{-- Operational pulse --}}
        @if($perluAksi > 0)
        <a href="{{ route('admin.orders') }}" class="pulse">
            <span class="pulse__dot" aria-hidden="true"></span>
            <span class="pulse__txt"><b>{{ $perluAksi }} pesanan</b> menunggu ditugaskan ke kurir</span>
            <svg class="pulse__arr" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </a>
        @else
        <div class="pulse">
            <span class="pulse__dot pulse__dot--ok" aria-hidden="true"></span>
            <span class="pulse__txt">Semua pesanan sudah tertangani. Kerja bagus!</span>
        </div>
        @endif
    </div>

    {{-- Wave --}}
    <svg class="hero__wave" viewBox="0 0 414 46" preserveAspectRatio="none" aria-hidden="true">
        <path fill="#eef4fa" d="M0,24 C70,46 150,6 230,24 C300,40 360,12 414,22 L414,46 L0,46Z"/>
    </svg>
</header>

{{-- ══════════════════════════════════════════════
     STAT TILES
══════════════════════════════════════════════ --}}
<section class="stats">
    <div class="stat reveal d1">
        <div class="stat__ico stat__ico--blue" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M7.5 4.27 16.5 9.4M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
        </div>
        <div class="stat__val">{{ $jumlahDiproses ?? 0 }}</div>
        <div class="stat__lbl">Diproses</div>
    </div>
    <div class="stat reveal d2">
        <div class="stat__ico stat__ico--orange" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
        </div>
        <div class="stat__val">{{ $jumlahPrioritas ?? 0 }}</div>
        <div class="stat__lbl">Belum Jemput</div>
    </div>
    <div class="stat reveal d3">
        <div class="stat__ico stat__ico--green" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
        </div>
        <div class="stat__val">{{ $jumlahSelesaiHari ?? 0 }}</div>
        <div class="stat__lbl">Selesai Ini</div>
    </div>
</section>

{{-- ══════════════════════════════════════════════
     BODY
══════════════════════════════════════════════ --}}
<main class="body">

    {{-- Revenue --}}
    <section class="revenue reveal d3" style="margin-top:18px;">
        <div class="revenue__head">
            <span class="revenue__ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M19 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"/><path d="M16 12h.01"/><path d="M3 9h18"/></svg>
            </span>
            <span class="revenue__cap">Pemasukan Hari Ini</span>
        </div>
        <div class="revenue__val"><span>Rp</span> {{ number_format($pemasukanHari ?? 0, 0, ',', '.') }}</div>
        <div class="revenue__foot">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/></svg>
            <span class="revenue__foot-txt">Bulan ini <b>Rp {{ number_format($pemasukanBulan ?? 0, 0, ',', '.') }}</b></span>
        </div>
    </section>

    {{-- Perlu Ditugaskan --}}
    <section class="section reveal d4">
        <div class="section__head">
            <div>
                <div class="section__title">Perlu Ditugaskan</div>
                <div class="section__title-sm">Pesanan baru yang menunggu kurir</div>
            </div>
            <a href="{{ route('admin.orders') }}" class="section__link">
                Semua
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
        <div class="order-scroll">
            @forelse(($orderPrioritas ?? collect()) as $order)
            @php
                $cust = $order->customer->name ?? 'Customer';
                $ci = mb_strtoupper(mb_substr($cust, 0, 1));
                $pickupTxt = $pickupLabels[$order->pickup_time] ?? ($order->pickup_time ?: 'Belum dijadwalkan');
            @endphp
            <article class="order-card">
                <div class="order-card__top">
                    <div class="order-card__user">
                        <div class="order-card__avatar" aria-hidden="true">{{ $ci }}</div>
                        <div>
                            <div class="order-card__name">{{ Str::limit($cust, 15) }}</div>
                            <div class="order-card__code">#{{ strtoupper($order->order_code) }}</div>
                        </div>
                    </div>
                    <span class="order-card__badge">Baru</span>
                </div>
                <div class="order-card__meta">
                    <div class="order-card__meta-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>{{ Str::limit($order->address ?? 'Alamat belum diisi', 32) }}</span>
                    </div>
                    <div class="order-card__meta-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <span>Dijemput {{ $pickupTxt }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.orders') }}" class="order-card__btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/></svg>
                    Tugaskan Kurir
                </a>
            </article>
            @empty
            <div class="order-empty">
                <div class="order-empty__ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>
                </div>
                <p>Tidak ada pesanan yang menunggu.<br>Semua sudah ditugaskan ke kurir.</p>
            </div>
            @endforelse
        </div>
    </section>

    {{-- Kurir Aktif --}}
    <section class="section reveal d4">
        <div class="section__head">
            <div>
                <div class="section__title">Kurir Bertugas</div>
                <div class="section__title-sm">{{ ($daftarDriver ?? collect())->count() }} kurir aktif hari ini</div>
            </div>
            <a href="{{ route('admin.orders') }}" class="section__link">
                Atur
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        </div>
        <div class="driver-scroll">
            @forelse(($daftarDriver ?? collect()) as $drv)
            <div class="driver-chip">
                <div class="driver-chip__av" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($drv->name, 0, 1)) }}
                    <span class="driver-chip__dot"></span>
                </div>
                <div>
                    <div class="driver-chip__name">{{ Str::limit(explode(' ', $drv->name)[0], 12) }}</div>
                    <div class="driver-chip__role">Siap antar-jemput</div>
                </div>
            </div>
            @empty
            <div class="driver-empty">Belum ada kurir aktif. Aktifkan kurir di menu pengaturan.</div>
            @endforelse
        </div>
    </section>

    {{-- Aksi Cepat --}}
    <section class="section reveal d5">
        <div class="section__head">
            <span class="section__title">Aksi Cepat</span>
        </div>
        <div class="quick-grid">
            <a href="{{ route('admin.walkin.form') }}" class="quick-item">
                <span class="quick-item__icon" style="background:var(--green-lt); color:var(--green);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                </span>
                <span class="quick-item__label">Pesanan Walk-in</span>
            </a>
            <a href="{{ route('admin.finance.index') }}" class="quick-item">
                <span class="quick-item__icon" style="background:#fff7e6; color:#b45309;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M19 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"/><path d="M16 12h.01M3 9h18"/></svg>
                </span>
                <span class="quick-item__label">Laporan Keuangan</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="quick-item">
                <span class="quick-item__icon" style="background:var(--red-lt); color:var(--red);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/></svg>
                </span>
                <span class="quick-item__label">Laporan Kendala</span>
            </a>
            <a href="{{ route('admin.orders') }}?status=selesai" class="quick-item">
                <span class="quick-item__icon" style="background:var(--teal-lt); color:var(--teal);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M11 12H3M16 6H3M16 18H3M18 9l3 3-3 3"/></svg>
                </span>
                <span class="quick-item__label">Pesanan Selesai</span>
            </a>
            <a href="{{ route('admin.vouchers.index') }}" class="quick-item">
                <span class="quick-item__icon" style="background:var(--orange-lt); color:#c2410c;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
                </span>
                <span class="quick-item__label">Voucher Promo</span>
            </a>
            <a href="{{ route('admin.categories.index') }}" class="quick-item">
                <span class="quick-item__icon" style="background:var(--blue-lt); color:var(--blue);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                </span>
                <span class="quick-item__label">Kategori &amp; Layanan</span>
            </a>
            <a href="{{ route('admin.audit.index') }}" class="quick-item">
                <span class="quick-item__icon" style="background:#f1f5f9; color:#475569;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v5h5M9 13h6M9 17h4"/></svg>
                </span>
                <span class="quick-item__label">Audit Trail</span>
            </a>
            <a href="{{ route('admin.notifications') }}" class="quick-item">
                <span class="quick-item__icon" style="background:var(--violet-lt); color:var(--violet);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </span>
                <span class="quick-item__label">Notifikasi</span>
            </a>
        </div>
    </section>

    {{-- Analitik 30 Hari --}}
    @include('roles.admin._analytics_section')

    {{-- Logout --}}
    <div class="logout-section reveal">
        <form action="{{ route('logout') }}" method="POST" id="form-logout">
            @csrf
            <button type="button" class="logout-btn" id="btn-logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>
                Keluar dari Akun
            </button>
        </form>
    </div>
    <div class="version-tag">Azka Laundry v{{ \App\Support\Laundry::version() }} &bull; Admin Panel</div>

</main>

@include('layouts.component.admin._navbar_admin', ['active' => 'beranda'])
@include('layouts.component._confirm_modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function () {
            showConfirmModal({
                title: 'Keluar dari Akun?',
                message: 'Kamu akan keluar dari sesi ini. Yakin ingin melanjutkan?',
                confirmText: 'Ya, Keluar',
                cancelText: 'Batal',
                type: 'danger',
                onConfirm: function () { document.getElementById('form-logout').submit(); }
            });
        });
    }
});
</script>
</body>
</html>
