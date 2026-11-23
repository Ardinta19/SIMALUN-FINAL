<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Dashboard Admin – Berkah Kreatif</title>
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
    --blue:       #85092d;
    --blue-mid:   #ad3553;
    --blue-dark:  #54051f;
    --blue-lt:    #f9e8ec;
    --sky:        #cf7488;
    --teal:       #0d9488;
    --teal-lt:    #ccfbf1;
    --orange:     #a92143;
    --amber:      #b45309;
    --orange-lt:  #fff1ea;
    --green:      #047857;
    --green-lt:   #e7faf2;
    --red:        #c62f2f;
    --red-lt:     #fff1f1;
    --violet:     #6366f1;
    --violet-lt:  #eef0ff;
    --surface:    #faf4f3;
    --card:       #ffffff;
    --ink:        #112030;
    --ink-mid:    #46586c;
    --ink-lt:     #5f7082;
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
        linear-gradient(140deg, #85092d 0%, #0090cd 52%, #00a6d6 100%);
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
.order-card__badge { font-size: 0.55rem; font-weight: 900; background: var(--orange-lt); color: #b8420f; border: 1px solid #ffd9c7; border-radius: 99px; padding: 4px 9px; text-transform: uppercase; letter-spacing: 0.4px; flex-shrink: 0; }
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
.driver-chip__av { position: relative; width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(145deg, #f9e8ec, #d3ecfb); color: var(--blue-dark); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 800; flex-shrink: 0; }
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
@include('layouts.component._theme_head')
</head>
<body>
@php
    $fullName = trim(auth()->user()->name ?? 'Admin');
    $adminName = explode(' ', $fullName)[0] ?: 'Admin';
    $initial = mb_strtoupper(mb_substr($adminName, 0, 1));
    $jam = (int) now('Asia/Jakarta')->format('H');
    $sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
    $tanggal = now('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM YYYY');
@endphp
<header class="hero">
    <span style="position:absolute;right:12%;top:20px;color:#ffffff88;font-size:36px" aria-hidden="true">✿</span>
    <div class="hero__inner"><div class="appbar"><div class="brand"><span class="brand__logo"><img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:12px;background:white"></span><div><span class="brand__name">Berkah Kreatif</span><span class="brand__tag">Admin Panel</span></div></div></div>
        <div class="greet"><div class="greet__avatar" aria-hidden="true">{{ $initial }}</div><div class="greet__text"><div class="greet__hi">{{ $sapaan }},</div><h1 class="greet__name">{{ $adminName }}</h1><span class="greet__date">{{ $tanggal }} · WIB</span></div></div>
        <a href="{{ route('admin.rental.orders', ['status' => 'pending']) }}" class="pulse"><span class="pulse__dot" aria-hidden="true"></span><span class="pulse__txt"><b>{{ $rentalPending }} unit</b> menunggu konfirmasi</span></a>
    </div>
    <svg class="hero__wave" viewBox="0 0 414 46" preserveAspectRatio="none" aria-hidden="true"><path fill="#faf4f3" d="M0,24 C70,46 150,6 230,24 C300,40 360,12 414,22 L414,46 L0,46Z"/></svg>
</header>
<section class="stats" aria-label="Ringkasan unit sewa">
    <div class="stat reveal d1"><div class="stat__ico stat__ico--blue" aria-hidden="true">🌸</div><div class="stat__val">{{ $rentalActive }}</div><div class="stat__lbl">Unit aktif</div></div>
    <div class="stat reveal d2"><div class="stat__ico stat__ico--orange" aria-hidden="true">📋</div><div class="stat__val">{{ $rentalPending }}</div><div class="stat__lbl">Menunggu</div></div>
    <div class="stat reveal d3"><div class="stat__ico stat__ico--green" aria-hidden="true">🔎</div><div class="stat__val">{{ $rentalReview }}</div><div class="stat__lbl">Tinjau manual</div></div>
</section>
<main class="body">
    <section class="revenue reveal d3" style="margin-top:18px"><div class="revenue__head"><span class="revenue__cap">Pemasukan hari ini · seluruh buku kas</span></div><div class="revenue__val"><span>Rp</span> {{ number_format($pemasukanHari ?? 0, 0, ',', '.') }}</div><div class="revenue__foot"><span class="revenue__foot-txt">Bulan ini <b>Rp {{ number_format($pemasukanBulan ?? 0, 0, ',', '.') }}</b></span></div></section>
    <section class="section reveal"><div class="section__head"><div><div class="section__title">Perlu perhatian</div><div class="section__title-sm">Buka tugas untuk memeriksa detail tiap unit</div></div></div>
        <a href="{{ route('admin.rental.orders', ['status' => 'pending']) }}" class="quick-item" style="margin-bottom:8px"><span class="quick-item__icon" aria-hidden="true">📋</span><span class="quick-item__label">{{ $rentalPending }} unit menunggu konfirmasi</span></a>
        <a href="{{ route('admin.rental.orders', ['status' => 'payment_pending']) }}" class="quick-item" style="margin-bottom:8px"><span class="quick-item__icon" aria-hidden="true">💳</span><span class="quick-item__label">{{ $rentalPaymentPending }} bukti menunggu pemeriksaan</span></a>
        <a href="{{ route('admin.rental.orders', ['status' => 'transport_quote']) }}" class="quick-item" style="margin-bottom:8px"><span class="quick-item__icon" aria-hidden="true">📍</span><span class="quick-item__label">{{ $rentalQuoteNeeded }} unit memerlukan penawaran ongkos</span></a>
        <a href="{{ route('admin.rental.orders', ['status' => 'design_initial']) }}" class="quick-item" style="margin-bottom:8px"><span class="quick-item__icon" aria-hidden="true">🌷</span><span class="quick-item__label">{{ $rentalDesignNeeded }} unit memerlukan preview awal</span></a>
        <a href="{{ route('admin.rental.orders', ['status' => 'review']) }}" class="quick-item" style="margin-bottom:8px"><span class="quick-item__icon" aria-hidden="true">🔎</span><span class="quick-item__label">{{ $rentalReview }} unit dalam peninjauan manual</span></a>
        <a href="{{ route('admin.rental.orders', ['status' => 'seller_cancelled']) }}" class="quick-item"><span class="quick-item__icon" aria-hidden="true">↩️</span><span class="quick-item__label">{{ $rentalSellerCancelled }} unit dibatalkan karena penjual · periksa tindak lanjut</span></a>
        <p style="font-size:.78rem;line-height:1.6;color:#705963;margin:10px 2px 0">Pembatalan karena penjual mencakup seluruh riwayat, termasuk yang sudah ditindaklanjuti.</p>
    </section>
    <section class="section reveal"><div class="section__head"><div><div class="section__title">Pesanan sewa terbaru</div><div class="section__title-sm">Periksa setiap unit secara terpisah</div></div><a href="{{ route('admin.rental.orders') }}" class="section__link">Semua →</a></div>
        @forelse($recentRentalOrders as $order)
        <a href="{{ route('admin.rental.orders', ['q' => $order->reference]) }}" class="quick-item" style="margin-bottom:8px"><span class="quick-item__icon" aria-hidden="true">💐</span><span class="quick-item__label" style="overflow-wrap:anywhere">{{ $order->customer?->name ?? 'Pelanggan tidak tersedia' }} · {{ $order->units_count }} unit<br><small>{{ $order->reference }}</small></span></a>
        @empty
        <div class="driver-empty">Belum ada pesanan sewa.</div>
        @endforelse
    </section>
    <section class="section reveal"><div class="section__head"><div class="section__title">Aksi Cepat</div></div><div class="quick-grid">
        <a href="{{ route('admin.rental.orders') }}" class="quick-item"><span class="quick-item__icon">📋</span><span class="quick-item__label">Pesanan sewa</span></a>
        <a href="{{ route('admin.rental.schedule') }}" class="quick-item"><span class="quick-item__icon">🗓️</span><span class="quick-item__label">Jadwal sewa</span></a>
        <a href="{{ route('admin.catalog.index') }}" class="quick-item"><span class="quick-item__icon">🌷</span><span class="quick-item__label">Kelola katalog</span></a>
        <a href="{{ route('admin.rental.universities.index') }}" class="quick-item"><span class="quick-item__icon">📍</span><span class="quick-item__label">Tarif universitas</span></a>
        <a href="{{ route('admin.rental.cashbook.index') }}" class="quick-item"><span class="quick-item__icon">📒</span><span class="quick-item__label">Buku kas</span></a>
        <a href="{{ route('admin.rental.payment-settings.edit') }}" class="quick-item"><span class="quick-item__icon">💳</span><span class="quick-item__label">Petunjuk pembayaran</span></a>
        <a href="{{ route('admin.notifications') }}" class="quick-item"><span class="quick-item__icon">🔔</span><span class="quick-item__label">Notifikasi</span></a>
        <a href="{{ route('admin.audit.index') }}" class="quick-item"><span class="quick-item__icon">🔐</span><span class="quick-item__label">Audit trail</span></a>
    </div></section>
    <p style="color:var(--ink-lt,#806975);font-size:.78rem;margin-top:20px">Sistem masih dalam pengujian dan belum untuk transaksi nyata. Ringkasan buku kas dapat mencakup data lama.</p>
    <div class="logout-section"><form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="logout-btn">Keluar dari Akun</button></form></div>
    <div class="version-tag">Berkah Kreatif · Admin Panel</div>
</main>
@include('layouts.component.admin._navbar_admin', ['active' => 'beranda'])
@include('layouts.component._form_controls')
@include('layouts.component._ui_kit')
</body>
</html>
