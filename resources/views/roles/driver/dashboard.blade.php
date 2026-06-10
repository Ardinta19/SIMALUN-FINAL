<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Dashboard Kurir – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Mode realtime: 'polling' (default, hemat) atau 'broadcasting' (Reverb,
     butuh VPS — lihat docs/realtime.md). Ganti via env DRIVER_REALTIME_MODE. --}}
<meta name="realtime-mode" content="{{ env('DRIVER_REALTIME_MODE', 'polling') }}">
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · KURIR · DASHBOARD (HUB)
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens
   emitted by layouts.component._tokens (mirrors tailwind.config.js).
═══════════════════════════════════════════════════════════ */
:root {
    /* Page-local aliases — only reference canonical tokens (no literals) */
    --ink:       var(--brand-900);
    --ink-muted: var(--surface-400);
    --line:      var(--surface-200);
    --line-soft: var(--surface-100);
    --font:      'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
    font-family: var(--font);
    font-size: 16px;
    background: var(--surface-50);
    color: var(--ink);
    min-height: 100vh;
    padding-bottom: calc(var(--space-20) + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}

/* ── Entrance animation (pure CSS — transform/opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}
.d4{animation-delay:.22s}.d5{animation-delay:.28s}.d6{animation-delay:.34s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ HERO */
.hero {
    background: linear-gradient(145deg, var(--brand-900) 0%, var(--brand-500) 70%, var(--accent-500) 160%);
    padding: max(env(safe-area-inset-top, 0px), var(--space-6)) var(--space-5) var(--space-8);
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    width: 180px; height: 180px;
    border-radius: var(--radius-pill);
    background: rgba(255,255,255,0.06);
    top: -50px; right: -30px;
}
.hero__inner { position: relative; z-index: 2; max-width: 520px; margin: 0 auto; }
.hero__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-5);
}
.hero__avatar {
    width: var(--space-11); height: var(--space-11);
    border-radius: var(--radius-pill);
    border: 2.5px solid rgba(255,255,255,0.5);
    object-fit: cover;
}
.notif-btn {
    width: var(--space-10); height: var(--space-10);
    border-radius: var(--radius-btn);
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    color: #fff; text-decoration: none;
    position: relative;
}
.notif-badge {
    position: absolute; top: -4px; right: -4px;
    background: var(--accent-500); color: #fff;
    font-size: 0.55rem; font-weight: 800;
    min-width: 16px; height: 16px;
    border-radius: var(--radius-pill);
    display: flex; align-items: center; justify-content: center;
    border: 2px solid var(--brand-900);
    padding: 0 3px;
}
.hero__greeting {
    font-size: 1.4rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
    margin-bottom: var(--space-1);
}
.hero__sub {
    font-size: 0.82rem;
    font-weight: 600;
    color: rgba(255,255,255,0.72);
}

/* KPI in hero */
.kpi-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-2);
    margin-top: var(--space-4);
}
.kpi-card {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: var(--radius-btn);
    padding: var(--space-3) var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
}
.kpi-card__ico {
    width: var(--space-9); height: var(--space-9);
    border-radius: var(--radius-btn);
    background: rgba(255,255,255,0.16);
    display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0;
}
.kpi-card__value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}
.kpi-card__label {
    font-size: 0.62rem;
    font-weight: 700;
    color: rgba(255,255,255,0.72);
    margin-top: var(--space-1);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* ═══════════════════════════════════════════════ CONTENT */
.content {
    max-width: 520px;
    margin: calc(var(--space-4) * -1) auto 0;
    padding: 0 var(--space-4);
    position: relative;
    z-index: 10;
}

/* SECTION HEADER */
.section-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: var(--space-5) 0 var(--space-3);
}
.section-title {
    display: flex; align-items: center; gap: var(--space-2);
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--ink);
}
.section-title svg { color: var(--brand-500); }
.section-link {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: 0.74rem;
    font-weight: 800;
    color: var(--brand-500);
    text-decoration: none;
}

/* TASK CARD */
.task-card {
    background: var(--surface-raised);
    border-radius: var(--radius-card);
    border: 1px solid var(--surface-200);
    margin-bottom: var(--space-3);
    overflow: hidden;
    box-shadow: var(--shadow-card);
}
.task-card--waiting { opacity: 0.72; }
.task-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--surface-100);
}
.task-card__code {
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--brand-500);
}
.task-card__status {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.6rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: var(--radius-pill);
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.task-card__status--pickup   { background: color-mix(in srgb, var(--accent-500) 14%, var(--surface-raised));  color: var(--accent-500); }
.task-card__status--delivery { background: color-mix(in srgb, var(--success-500) 16%, var(--surface-raised)); color: var(--success-500); }
.task-card__status--waiting  { background: color-mix(in srgb, var(--warning-500) 16%, var(--surface-raised)); color: var(--warning-500); }

.task-card__body { padding: var(--space-3) var(--space-4); }
.task-card__customer {
    font-size: 0.92rem;
    font-weight: 800;
    color: var(--ink);
    margin-bottom: var(--space-2);
}
.task-card__address {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ink-muted);
    line-height: 1.5;
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    margin-bottom: var(--space-2);
}
.task-card__address svg { color: var(--brand-500); flex-shrink: 0; margin-top: 1px; }
.task-card__weight {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--brand-100);
    color: var(--brand-500);
    border-radius: var(--radius-pill);
    padding: 4px 10px;
    font-size: 0.7rem;
    font-weight: 700;
}
.task-card__actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-2);
    margin-top: var(--space-3);
}
.task-btn {
    min-height: var(--space-11);
    padding: var(--space-2);
    border-radius: var(--radius-btn);
    border: none;
    font-family: var(--font);
    font-weight: 800;
    font-size: 0.78rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    text-decoration: none;
    transition: transform 0.12s ease;
}
.task-btn:active { transform: scale(0.97); }
.task-btn--primary { background: var(--brand-500); color: #fff; }
.task-btn--success { background: var(--success-500); color: #fff; }
.task-btn--muted   { background: var(--surface-100); color: var(--ink-muted); cursor: default; }
@media (prefers-reduced-motion: reduce){ .task-btn { transition: none; } }

/* EMPTY STATE */
.empty-card {
    background: var(--surface-raised);
    border-radius: var(--radius-card);
    border: 1.5px dashed var(--surface-200);
    padding: var(--space-10) var(--space-5);
    text-align: center;
}
.empty-card__ico {
    width: var(--space-14); height: var(--space-14);
    margin: 0 auto var(--space-3);
    border-radius: var(--radius-pill);
    background: var(--brand-100);
    display: flex; align-items: center; justify-content: center;
    color: var(--success-500);
}
.empty-card__text { font-size: 0.85rem; font-weight: 700; color: var(--ink-muted); line-height: 1.5; }

/* LOGOUT */
.logout-section { margin-top: var(--space-5); margin-bottom: var(--space-3); }
.logout-btn {
    width: 100%;
    min-height: var(--space-12);
    padding: var(--space-3);
    border-radius: var(--radius-btn);
    border: 1px solid color-mix(in srgb, var(--danger-500) 35%, var(--surface-raised));
    background: color-mix(in srgb, var(--danger-500) 8%, var(--surface-raised));
    color: var(--danger-500);
    font-family: var(--font);
    font-size: 0.85rem;
    font-weight: 800;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    transition: transform 0.12s ease;
}
.logout-btn:active { transform: scale(0.98); }
@media (prefers-reduced-motion: reduce){ .logout-btn { transition: none; } }
.version-tag {
    text-align: center;
    font-size: 0.62rem;
    font-weight: 700;
    color: var(--ink-muted);
    letter-spacing: 0.3px;
    margin-bottom: var(--space-2);
}

/* ── Realtime update banner (token-styled, transform/opacity only) ── */
.rt-banner {
    position: fixed; left: 50%; top: var(--space-4); transform: translateX(-50%);
    display: flex; align-items: center; gap: var(--space-2);
    background: var(--brand-500); color: #fff;
    padding: var(--space-3) var(--space-5); border-radius: var(--radius-pill);
    font-family: var(--font); font-weight: 800; font-size: .78rem;
    z-index: 1000; box-shadow: var(--shadow-card); cursor: pointer;
    animation: riseIn .3s ease;
}
@media (prefers-reduced-motion: reduce){ .rt-banner { animation: none; } }
</style>
</head>
<body>

{{-- ══════════════ HERO ══════════════ --}}
<div class="hero reveal d1">
    <div class="hero__inner">
        <div class="hero__top">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($driver->name) }}&background=0077b6&color=fff&size=128"
                 class="hero__avatar" alt="{{ $driver->name }}">
            <a href="{{ route('driver.notifications') }}" class="notif-btn" aria-label="Notifikasi">
                @include('layouts.component._icon', ['name' => 'notif', 'size' => 20, 'label' => 'Notifikasi'])
                @if($unreadNotif > 0)
                <span class="notif-badge">{{ $unreadNotif > 9 ? '9+' : $unreadNotif }}</span>
                @endif
            </a>
        </div>
        <div class="hero__greeting">Halo, {{ explode(' ', $driver->name)[0] }}!</div>
        <div class="hero__sub">Berikut tugas jemput &amp; antar hari ini.</div>
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-card__ico">
                    @include('layouts.component._icon', ['name' => 'tugas', 'size' => 20])
                </div>
                <div>
                    <div class="kpi-card__value">{{ $tugasAktif->count() }}</div>
                    <div class="kpi-card__label">Tugas Aktif</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-card__ico">
                    @include('layouts.component._icon', ['name' => 'delivery', 'size' => 20])
                </div>
                <div>
                    <div class="kpi-card__value">{{ $totalAntarBulanIni }}</div>
                    <div class="kpi-card__label">Antar Bulan Ini</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════ CONTENT ══════════════ --}}
<div class="content">

    {{-- Tugas Aktif --}}
    <div class="section-hd reveal d2">
        <span class="section-title">
            @include('layouts.component._icon', ['name' => 'tugas', 'size' => 20])
            Tugas Aktif
        </span>
        <a href="{{ route('driver.orders') }}" class="section-link">
            Lihat Semua
            @include('layouts.component._icon', ['name' => 'next', 'size' => 16])
        </a>
    </div>

    @forelse($tugasAktif as $order)
    <div class="task-card reveal d3">
        <div class="task-card__head">
            <div class="task-card__code">#{{ strtoupper($order->order_code) }}</div>
            <span class="task-card__status {{ $order->status === 'dijemput' ? 'task-card__status--pickup' : 'task-card__status--delivery' }}">
                @include('layouts.component._icon', ['name' => $order->status === 'dijemput' ? 'pickup' : 'delivery', 'size' => 16])
                {{ $order->status === 'dijemput' ? 'Jemput' : 'Antar' }}
            </span>
        </div>
        <div class="task-card__body">
            <div class="task-card__customer">{{ $order->customer->name ?? 'Customer' }}</div>
            <div class="task-card__address">
                @include('layouts.component._icon', ['name' => 'address', 'size' => 16])
                {{ Str::limit($order->customerAddress?->full_address ?? $order->address ?? '-', 55) }}
            </div>
            <span class="task-card__weight">
                @include('layouts.component._icon', ['name' => 'laundry', 'size' => 16])
                {{ $order->weight_estimate }} kg est.
            </span>
            <div class="task-card__actions">
                @if($order->customer?->phone)
                <a href="{{ \App\Support\WhatsApp::link($order->customer->phone, \App\Support\WhatsApp::customerMessage($order, $order->status === 'dijemput' ? 'pickup' : 'delivery')) }}"
                   target="_blank" rel="noopener" class="task-btn task-btn--primary">
                    @include('layouts.component._icon', ['name' => 'whatsapp', 'size' => 16])
                    Hubungi
                </a>
                @else
                <div class="task-btn task-btn--muted">Tidak ada HP</div>
                @endif
                <a href="{{ route('driver.orders.show', $order) }}" class="task-btn task-btn--success">
                    @include('layouts.component._icon', ['name' => 'next', 'size' => 16])
                    Detail
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="empty-card reveal d3">
        <div class="empty-card__ico" aria-hidden="true">
            @include('layouts.component._icon', ['name' => 'success', 'size' => 32])
        </div>
        <div class="empty-card__text">Tidak ada tugas aktif saat ini.<br>Semua sudah diselesaikan!</div>
    </div>
    @endforelse

    {{-- Tugas Menunggu --}}
    @if(isset($tugasMenunggu) && $tugasMenunggu->count() > 0)
    <div class="section-hd reveal d4">
        <span class="section-title">
            @include('layouts.component._icon', ['name' => 'menunggu', 'size' => 20])
            Menunggu Konfirmasi
        </span>
    </div>
    @foreach($tugasMenunggu->take(3) as $order)
    <div class="task-card task-card--waiting reveal d4">
        <div class="task-card__head">
            <div class="task-card__code">#{{ strtoupper($order->order_code) }}</div>
            <span class="task-card__status task-card__status--waiting">
                @include('layouts.component._icon', ['name' => 'menunggu', 'size' => 16])
                Menunggu
            </span>
        </div>
        <div class="task-card__body">
            <div class="task-card__customer">{{ $order->customer->name ?? '-' }}</div>
            <div class="task-card__address">
                @include('layouts.component._icon', ['name' => 'address', 'size' => 16])
                {{ Str::limit($order->address ?? '-', 50) }}
            </div>
        </div>
    </div>
    @endforeach
    @endif

    {{-- Logout --}}
    <div class="logout-section reveal d5">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-btn">
                @include('layouts.component._icon', ['name' => 'logout', 'size' => 20])
                Keluar dari Akun
            </button>
        </form>
    </div>
    <div class="version-tag">AZKA LAUNDRY &bull; Kurir v{{ \App\Support\Laundry::version() }}</div>

</div>

{{-- ══════════════ DRIVER NAV ══════════════ --}}
@include('layouts.component.driver._navbar_driver', ['active' => 'beranda'])

<script>
/* ───────────────── Realtime Driver Dashboard ────────────────────
 * Mode dibaca dari <meta name="realtime-mode">.
 *  - polling (default): cek endpoint setiap 30 detik. Hemat & jalan
 *    di shared hosting.
 *  - broadcasting: pakai Echo + Reverb. Belum diaktifkan di repo —
 *    lihat docs/realtime.md untuk panduan switch.
 *
 * Untuk hindari nge-spam server kalau tab gak aktif, polling auto-pause
 * saat document.hidden = true.
 */
(function () {
    const mode = document.querySelector('meta[name="realtime-mode"]')?.content || 'polling';

    if (mode !== 'polling') {
        // ── BROADCASTING MODE (template, tidak aktif) ─────────────
        // Saat pindah ke VPS, ikuti docs/realtime.md sampai langkah
        // bikin echo.js, lalu uncomment block di bawah & hapus
        // polling-init di bawah.
        //
        // import './echo.js';
        // window.Echo.private(`App.Models.User.${currentUserId}`)
        //     .notification((notification) => {
        //         if (notification.type === 'App\\Notifications\\OrderStatusUpdated') {
        //             window.location.reload();
        //         }
        //     });
        return;
    }

    let lastSignature = null;
    const POLL_INTERVAL_MS = 30000;

    async function poll() {
        if (document.hidden) return; // hemat resource saat tab background

        try {
            const res = await fetch('{{ route('driver.dashboard.poll') }}', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (! res.ok) return;
            const data = await res.json();

            if (lastSignature === null) {
                lastSignature = data.signature;
                return;
            }

            // Signature berubah = ada update di backend (order baru
            // di-assign / status berubah). Tampilkan banner supaya kartu
            // bisa sinkron tanpa kompleksitas patch DOM manual.
            if (data.signature !== lastSignature) {
                lastSignature = data.signature;
                showNewTaskBanner();
            }
        } catch (e) {
            // Diam — bisa jadi user offline sebentar. Polling berikutnya
            // akan retry otomatis.
        }
    }

    function showNewTaskBanner() {
        if (document.getElementById('rt-banner')) return; // udah ada

        const banner = document.createElement('div');
        banner.id = 'rt-banner';
        banner.className = 'rt-banner';
        banner.textContent = 'Ada update tugas — tap untuk muat ulang';
        banner.addEventListener('click', () => window.location.reload());
        document.body.appendChild(banner);
    }

    // First poll cepat, biar sig awal di-cache. Setelah itu interval normal.
    setTimeout(poll, 1500);
    setInterval(poll, POLL_INTERVAL_MS);
})();
</script>
</body>
</html>
