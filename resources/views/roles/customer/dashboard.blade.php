<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Dashboard – Berkah Kreatif</title>
@include('layouts.component.customer._head_meta')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<style>
/* ═══════════════════════════════════════════════
   TOKENS
═══════════════════════════════════════════════ */
:root {
  --c-ocean-deep:  #54051f;
  --c-ocean-mid:   #85092d;
  --c-ocean-light: #a52b4e;
  --c-ocean-sky:   #cf7488;
  --c-fire:        #85092d;
  --c-fire-soft:   #b63050;
  --c-mint:        #00C48C;
  --c-surface:     #faf4f3;
  --c-card:        #ffffff;
  --c-ink:         #0d2137;
  --c-ink-mid:     #3a546b;
  --c-ink-soft:    #7a9ab0;
  --c-line:        #f0dfe1;
  --c-line-soft:   #fcf1f3;
  --nav-h:         68px;
  --r-card:        20px;
  --r-pill:        99px;
}

*, *::before, *::after {
  margin: 0; padding: 0;
  box-sizing: border-box;
  -webkit-tap-highlight-color: transparent;
}
html { scroll-behavior: smooth; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--c-surface);
  color: var(--c-ink);
  min-height: 100vh;
  overflow-x: hidden;
  padding-bottom: calc(var(--nav-h) + env(safe-area-inset-bottom, 0px) + 16px);
}

/* ═══════════════════════════════════════════════
   HEADER
═══════════════════════════════════════════════ */
.hd {
  background: linear-gradient(168deg,
    var(--c-ocean-deep) 0%,
    var(--c-ocean-mid)  55%,
    var(--c-ocean-light) 100%
  );
  position: relative;
  overflow: hidden;
  padding-top: max(env(safe-area-inset-top, 0px), 12px);
  /* wave bottom — menyambung ke konten */
  padding-bottom: 48px;
}

/* gelembung dekoratif */
.hd-bubble {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 28%,
    rgba(255,255,255,.55), rgba(255,255,255,.04));
  border: 1.5px solid rgba(255,255,255,.22);
  pointer-events: none;
}

/* wave SVG di bawah header */
.hd-wave {
  position: absolute;
  bottom: -1px; left: -2%; right: -2%;
  pointer-events: none;
}

.hd-inner {
  max-width: 520px;
  margin: 0 auto;
  padding: 14px 20px 0;
  position: relative;
  z-index: 2;
}

/* baris logo + aksi */
.hd-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}
.hd-logo {
  display: flex;
  align-items: center;
  gap: 9px;
}
.hd-logo-text {
  font-weight: 800;
  font-size: 1.1rem;
  color: #fff;
  letter-spacing: .2px;
  text-shadow: 0 2px 8px rgba(0,0,0,.2);
  line-height: 1;
}
.hd-logo-sub {
  display: block;
  font-size: .52rem;
  font-weight: 800;
  color: rgba(144,224,239,.85);
  letter-spacing: 3.5px;
  text-transform: uppercase;
  margin-top: 1px;
}
.hd-icons {
  display: flex;
  align-items: center;
  gap: 8px;
}
.hd-icon-btn {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: rgba(255,255,255,.14);
  border: 1.5px solid rgba(255,255,255,.24);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background .2s;
  text-decoration: none;
  position: relative;
  flex-shrink: 0;
}
.hd-icon-btn:hover { background: rgba(255,255,255,.26); }
.hd-icon-btn svg { width: 17px; height: 17px; stroke: #fff; }
.notif-pip {
  position: absolute;
  top: 6px; right: 6px;
  width: 8px; height: 8px;
  background: var(--c-fire);
  border-radius: 50%;
  border: 1.5px solid var(--c-ocean-mid);
}

/* greeting */
.hd-greet {
  font-size: .72rem;
  font-weight: 700;
  color: rgba(255,255,255,.68);
  margin-bottom: 3px;
  letter-spacing: .2px;
}
.hd-name {
  font-weight: 800;
  font-size: clamp(1.65rem, 6.5vw, 2rem);
  color: #fff;
  line-height: 1;
  margin-bottom: 5px;
  text-shadow: 0 2px 14px rgba(0,0,0,.22);
}
.hd-slogan {
  font-size: .72rem;
  font-weight: 700;
  color: rgba(144,224,239,.9);
  letter-spacing: .3px;
  font-style: italic;
}

/* ═══════════════════════════════════════════════
   HERO CARD (pesanan aktif) — melayang di atas wave
   menggunakan negative margin supaya overlap
═══════════════════════════════════════════════ */
.page-body {
  max-width: 520px;
  margin: 0 auto;
  padding: 0 16px;
  /* overlap hero card ke atas wave */
  margin-top: -36px;
  position: relative;
  z-index: 5;
}

/* ═══════════════════════════════════════════════
   CTA JEMPUT — HERO UTAMA (80% pelanggan jemput antar!)
═══════════════════════════════════════════════ */
.cta-jemput {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, var(--c-fire) 0%, var(--c-fire-soft) 100%);
  border-radius: var(--r-card);
  padding: 16px 18px;
  margin-bottom: 14px;
  text-decoration: none;
  color: #fff;
  box-shadow:
    0 12px 32px rgba(255,107,53,.38),
    0 4px 8px rgba(255,107,53,.2),
    inset 0 1px 0 rgba(255,255,255,.2);
  position: relative;
  overflow: hidden;
  transition: transform .15s, box-shadow .15s;
}
.cta-jemput:active {
  transform: scale(.97);
  box-shadow: 0 6px 16px rgba(255,107,53,.3);
}
.cta-jemput::before {
  content: '';
  position: absolute;
  top: 0; left: -100%; width: 55%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
  animation: shimmer 3s ease-in-out infinite;
}
@keyframes shimmer { 0%{left:-100%} 55%,100%{left:160%} }

/* drum icon di dalam CTA */
.cta-drum {
  width: 48px; height: 48px;
  background: rgba(255,255,255,.18);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cta-texts { flex: 1; padding: 0 12px; }
.cta-label {
  display: block;
  font-weight: 800;
  font-size: 1.05rem;
  line-height: 1.1;
  margin-bottom: 3px;
}
.cta-sub {
  display: block;
  font-size: .68rem;
  font-weight: 700;
  color: rgba(255,255,255,.78);
}
.cta-arr {
  width: 34px; height: 34px;
  background: rgba(255,255,255,.2);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* ═══════════════════════════════════════════════
   ACTIVE ORDER HERO CARD
═══════════════════════════════════════════════ */
.active-hero {
  background: #fff;
  border-radius: var(--r-card);
  border: 1.5px solid var(--c-line);
  margin-bottom: 14px;
  overflow: hidden;
  box-shadow:
    0 8px 28px rgba(0,47,92,.08),
    0 2px 6px rgba(0,47,92,.04);
}
/* accent bar kiri — seperti status aktif */
.active-hero::before {
  content: '';
  display: block;
  height: 4px;
  background: linear-gradient(90deg, var(--c-ocean-mid), var(--c-ocean-sky));
  border-radius: 99px 99px 0 0;
}
.ah-top {
  padding: 14px 16px 12px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.ah-order-id {
  font-weight: 800;
  font-size: .82rem;
  color: var(--c-ocean-mid);
  letter-spacing: .5px;
}
.ah-service {
  font-size: .72rem;
  font-weight: 700;
  color: var(--c-ink-soft);
  margin-top: 2px;
}
.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: .62rem;
  font-weight: 900;
  padding: 4px 10px;
  border-radius: 99px;
  white-space: nowrap;
  letter-spacing: .3px;
}
.sp-washing {
  background: rgba(0,116,180,.1);
  color: var(--c-ocean-mid);
  border: 1px solid rgba(0,116,180,.22);
}
.sp-pickup {
  background: rgba(255,107,53,.1);
  color: #b84a14;
  border: 1px solid rgba(255,107,53,.2);
}
.sp-ready {
  background: rgba(0,196,140,.1);
  color: #047857;
  border: 1px solid rgba(0,196,140,.22);
}
.sp-done {
  background: rgba(0,196,140,.12);
  color: #065f46;
  border: 1px solid rgba(0,196,140,.25);
}
/* pulsing dot */
.live-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: currentColor;
  animation: livepulse 1.6s ease-in-out infinite;
}
@keyframes livepulse {
  0%,100% { opacity: 1; transform: scale(1); }
  50%      { opacity: .4; transform: scale(1.5); }
}

/* ── STEPPER PROGRESS ── */
.stepper {
  display: flex;
  align-items: flex-start;
  padding: 0 16px 14px;
  gap: 0;
}
.st-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  flex: 1;
}
.st-line {
  flex: 1;
  height: 2px;
  background: var(--c-line);
  border-radius: 99px;
  margin-top: 12px;
  transition: background .4s;
}
.st-line.done { background: var(--c-ocean-mid); }

.st-dot {
  width: 24px; height: 24px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-size: .6rem;
  font-weight: 900;
  border: 2px solid var(--c-line);
  background: #fff;
  color: var(--c-ink-soft);
  transition: all .35s;
  z-index: 1;
}
.st-dot.done {
  background: var(--c-ocean-mid);
  border-color: var(--c-ocean-mid);
  color: #fff;
}
.st-dot.active {
  background: #fff;
  border-color: var(--c-ocean-mid);
  border-width: 2.5px;
  color: var(--c-ocean-mid);
  box-shadow: 0 0 0 4px rgba(0,119,182,.12);
}
.st-lbl {
  font-size: .58rem;
  font-weight: 800;
  color: var(--c-ink-soft);
  text-align: center;
  line-height: 1.2;
  white-space: nowrap;
}
.st-lbl.done   { color: var(--c-ocean-mid); }
.st-lbl.active { color: var(--c-ink); }

/* ── FOOTER INFO ── */
.ah-footer {
  display: flex;
  align-items: center;
  padding: 11px 16px;
  border-top: 1.5px solid var(--c-line-soft);
  gap: 12px;
  background: rgba(0,116,180,.02);
}
.ah-meta {
  flex: 1;
  display: flex;
  gap: 16px;
}
.ah-meta-item {}
.ah-meta-lbl {
  font-size: .58rem;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .7px;
  color: var(--c-ink-soft);
}
.ah-meta-val {
  font-size: .82rem;
  font-weight: 900;
  color: var(--c-ink);
  margin-top: 1px;
}
.btn-lacak {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--c-ocean-mid);
  color: #fff;
  text-decoration: none;
  border-radius: 99px;
  padding: 7px 15px;
  font-size: .72rem;
  font-weight: 900;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0,119,182,.3);
  transition: background .15s, transform .15s;
  letter-spacing: .2px;
}
.btn-lacak:active { transform: scale(.95); }

/* empty state */
.no-order {
  background: #fff;
  border-radius: var(--r-card);
  border: 2px dashed var(--c-line);
  padding: 28px 20px;
  text-align: center;
  margin-bottom: 14px;
}
.no-order-icon {
  font-size: 2.8rem;
  margin-bottom: 10px;
  display: block;
}
.no-order-txt {
  font-size: .8rem;
  font-weight: 700;
  color: var(--c-ink-soft);
  line-height: 1.5;
  margin-bottom: 14px;
}

/* ═══════════════════════════════════════════════
   SECTION LABEL
═══════════════════════════════════════════════ */
.sec-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 18px 0 10px;
}
.sec-title {
  font-weight: 800;
  font-size: .95rem;
  color: var(--c-ink);
  letter-spacing: .2px;
}
.sec-link {
  font-size: .7rem;
  font-weight: 800;
  color: var(--c-ocean-mid);
  text-decoration: none;
  letter-spacing: .2px;
}
.sec-link:hover { text-decoration: underline; }

/* ═══════════════════════════════════════════════
   STATS ROW — 2 kartu saja, tidak cramped
═══════════════════════════════════════════════ */
.stats-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 14px;
}
.stat-card {
  background: #fff;
  border: 1.5px solid var(--c-line);
  border-radius: var(--r-card);
  padding: 14px 16px;
  box-shadow: 0 2px 8px rgba(0,47,92,.04);
  position: relative;
  overflow: hidden;
}
.stat-card::after {
  content: '';
  position: absolute;
  bottom: -10px; right: -10px;
  width: 56px; height: 56px;
  border-radius: 50%;
  background: rgba(0,119,182,.05);
}
.stat-ico {
  font-size: 1.3rem;
  margin-bottom: 8px;
  display: block;
}
.stat-num {
  font-weight: 800;
  font-size: 1.8rem;
  color: var(--c-ocean-mid);
  line-height: 1;
}
.stat-lbl {
  font-size: .65rem;
  font-weight: 900;
  color: var(--c-ink-soft);
  text-transform: uppercase;
  letter-spacing: .7px;
  margin-top: 4px;
}

/* ═══════════════════════════════════════════════
   PROMO BANNER
═══════════════════════════════════════════════ */
.promo-banner {
  background: linear-gradient(135deg,
    var(--c-ocean-deep) 0%,
    var(--c-ocean-mid) 100%
  );
  border-radius: var(--r-card);
  padding: 18px 20px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 14px;
  text-decoration: none;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,47,92,.2);
  transition: transform .15s;
}
.promo-banner:active { transform: scale(.97); }
/* deco circle */
.promo-banner::after {
  content: '';
  position: absolute;
  width: 130px; height: 130px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
  top: -40px; right: -30px;
  pointer-events: none;
}
.promo-tag {
  background: var(--c-fire);
  color: #fff;
  font-weight: 800;
  font-size: 1.7rem;
  padding: 8px 14px;
  border-radius: 14px;
  flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(255,107,53,.4);
}
.promo-texts { flex: 1; }
.promo-title {
  font-weight: 800;
  font-size: 1rem;
  color: #fff;
  line-height: 1.2;
  margin-bottom: 3px;
}
.promo-sub {
  font-size: .68rem;
  font-weight: 700;
  color: rgba(144,224,239,.88);
}
.promo-arr {
  color: rgba(255,255,255,.4);
  font-size: 1.4rem;
  flex-shrink: 0;
}

/* ═══════════════════════════════════════════════
   AKSI CEPAT — icon grid 2x2
═══════════════════════════════════════════════ */
.quick-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
  margin-bottom: 14px;
}
.quick-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  padding: 12px 4px;
  background: #fff;
  border: 1.5px solid var(--c-line);
  border-radius: 16px;
  transition: border-color .2s, box-shadow .2s;
  box-shadow: 0 1px 4px rgba(0,47,92,.04);
}
.quick-item:active {
  border-color: var(--c-ocean-mid);
  box-shadow: 0 0 0 3px rgba(0,119,182,.1);
}
.quick-ico {
  width: 40px; height: 40px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}
.qi-blue   { background: rgba(0,116,180,.1); }
.qi-orange { background: rgba(255,107,53,.1); }
.qi-green  { background: rgba(0,196,140,.1); }
.qi-sky    { background: rgba(0,180,216,.1); }
.quick-lbl {
  font-size: .62rem;
  font-weight: 900;
  color: var(--c-ink-mid);
  text-align: center;
  line-height: 1.2;
  letter-spacing: .2px;
}

/* ═══════════════════════════════════════════════
   RIWAYAT PESANAN
═══════════════════════════════════════════════ */
.history-card {
  background: #fff;
  border: 1.5px solid var(--c-line);
  border-radius: var(--r-card);
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,47,92,.04);
  margin-bottom: 14px;
}
.hist-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 16px;
  border-bottom: 1px solid var(--c-line-soft);
  text-decoration: none;
  color: inherit;
  transition: background .15s;
}
.hist-item:last-child { border-bottom: none; }
.hist-item:active { background: var(--c-surface); }
.hist-ico {
  width: 42px; height: 42px;
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.hist-texts { flex: 1; min-width: 0; }
.hist-name {
  font-size: .85rem;
  font-weight: 800;
  color: var(--c-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.hist-code {
  font-size: .68rem;
  font-weight: 700;
  color: var(--c-ink-soft);
  margin-top: 2px;
}
.hist-done-badge {
  display: inline-block;
  margin-top: 4px;
  font-size: .58rem;
  font-weight: 900;
  background: rgba(0,196,140,.1);
  color: #047857;
  padding: 2px 8px;
  border-radius: 99px;
  border: 1px solid rgba(0,196,140,.22);
  letter-spacing: .3px;
}
.hist-right { text-align: right; flex-shrink: 0; }
.hist-price {
  font-weight: 800;
  font-size: .95rem;
  color: var(--c-ocean-mid);
}
.hist-date {
  font-size: .62rem;
  font-weight: 700;
  color: var(--c-ink-soft);
  margin-top: 2px;
}

/* empty history */
.hist-empty {
  padding: 24px;
  text-align: center;
  font-size: .78rem;
  font-weight: 700;
  color: var(--c-ink-soft);
}


/* ═══════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════ */
@media (min-width: 560px) {
  .page-body { padding: 0 24px; }
}

/* ═══════════════════════════════════════════════
   ENTRANCE ANIMATIONS
═══════════════════════════════════════════════ */
.js-reveal {
  opacity: 0;
  transform: translateY(22px);
}
</style>
</head>
<body>

{{-- ══════════════════════════════════════
     HEADER
══════════════════════════════════════ --}}
<header class="hd" aria-label="Header dashboard">

  <span class="floral-deco" style="top:18px;right:8%;font-size:34px" aria-hidden="true">✿</span>
  <span class="floral-deco" style="top:90px;left:8%;font-size:24px" aria-hidden="true">✿</span>
  <div class="hd-inner">
    {{-- Topbar --}}
    <div class="hd-topbar">
      <div class="hd-logo">
        <img src="{{ asset('images/berkah-kreatif-logo.jpeg') }}" alt="" width="40" height="40" style="object-fit:contain;border-radius:12px;background:white">
        <div>
          <span class="hd-logo-text">Berkah Kreatif</span>
          <span class="hd-logo-sub">Papan bunga</span>
        </div>
      </div>

      <div class="hd-icons">
        <a href="{{ route('welcome') }}" class="hd-icon-btn" title="Pesan baru" aria-label="Buat pesanan baru">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
        </a>
        <a href="{{ route('customer.notifications') }}" class="hd-icon-btn" title="Notifikasi" aria-label="Notifikasi">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>
          </svg>
          @if(isset($unreadNotif) && $unreadNotif > 0)
            <span class="notif-pip" aria-label="{{ $unreadNotif }} notifikasi belum dibaca"></span>
          @endif
        </a>
      </div>
    </div>

    {{-- Greeting --}}
    <p class="hd-greet">Selamat datang kembali</p>
    <h1 class="hd-name">Halo, {{ explode(' ', auth()->user()->name)[0] }}!</h1>
    <p class="hd-slogan">Bunga untuk setiap momen berharga.</p>
  </div>

  {{-- Wave SVG — menyambung ke konten --}}
  <svg class="hd-wave" viewBox="0 0 414 52" preserveAspectRatio="none" aria-hidden="true">
    <path fill="#faf4f3"
      d="M0,28 C80,52 160,8 240,28 C300,44 360,12 414,24 L414,52 L0,52Z"/>
  </svg>
</header>

{{-- ══════════════════════════════════════
     PAGE BODY
══════════════════════════════════════ --}}
<main class="page-body" role="main">
  <a href="{{ route('welcome') }}" class="cta-jemput js-reveal" aria-label="Jelajahi katalog papan bunga">
    <div class="cta-drum" aria-hidden="true" style="font-size:28px">🌸</div>
    <div class="cta-texts"><span class="cta-label">Pilih papan bunga</span><span class="cta-sub">Temukan bentuk yang cocok untuk momenmu</span></div>
    <div class="cta-arr" aria-hidden="true">→</div>
  </a>
  <div class="js-reveal"><div class="stats-row"><div class="stat-card"><span class="stat-ico" aria-hidden="true">🌷</span><div class="stat-num">{{ $totalOrders }}</div><div class="stat-lbl">Pesanan</div></div><div class="stat-card"><span class="stat-ico" aria-hidden="true">📦</span><div class="stat-num">{{ $activeUnits }}</div><div class="stat-lbl">Unit aktif</div></div></div></div>
  <div class="js-reveal"><div class="sec-label"><span class="sec-title">Aksi Cepat</span></div><div class="quick-grid" role="navigation" aria-label="Menu aksi cepat">
    <a href="{{ route('welcome') }}" class="quick-item"><div class="quick-ico qi-orange" aria-hidden="true">🌸</div><span class="quick-lbl">Katalog</span></a>
    <a href="{{ route('cart.index') }}" class="quick-item"><div class="quick-ico qi-blue" aria-hidden="true">🛒</div><span class="quick-lbl">Keranjang</span></a>
    <a href="{{ route('rental.orders.index') }}" class="quick-item"><div class="quick-ico qi-green" aria-hidden="true">📋</div><span class="quick-lbl">Pesanan</span></a>
    <a href="{{ route('customer.profile') }}" class="quick-item"><div class="quick-ico qi-sky" aria-hidden="true">👤</div><span class="quick-lbl">Profil</span></a>
  </div></div>
  <div class="js-reveal"><div class="sec-label"><span class="sec-title">Pesanan terbaru</span><a href="{{ route('rental.orders.index') }}" class="sec-link">Semua →</a></div><div class="history-card">
  @forelse($orders as $order)
    <a href="{{ route('rental.orders.show', $order) }}" class="hist-item"><div class="hist-ico qi-orange" aria-hidden="true">💐</div><div class="hist-texts"><div class="hist-name">{{ $order->units_count }} unit papan bunga</div><div class="hist-code">{{ $order->reference }}</div></div><div class="hist-right"><div class="hist-date">{{ $order->created_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y') }}</div></div></a>
  @empty
    <div class="hist-empty">Belum ada pesanan papan bunga.</div>
  @endforelse
  </div></div>
  <p style="font-size:.78rem;color:#806975;margin:16px 0">Sistem masih dalam pengujian dan belum untuk transaksi nyata.</p>
</main>

{{-- BOTTOM NAVIGATION (shared partial) --}}
@include('layouts.component.customer._navbar_customer', ['active' => 'beranda'])

<style>.floral-deco{position:absolute;color:rgba(255,255,255,.38);pointer-events:none}.hist-code{overflow-wrap:anywhere}.js-reveal{opacity:1!important;transform:none!important}</style>
</body>
</html>
