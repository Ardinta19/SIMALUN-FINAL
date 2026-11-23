{{--
    Partial: _navbar_admin.blade.php
    Usage: @include('layouts.component.admin._navbar_admin')

    Dipakai halaman admin mandiri yang belum dimigrasikan ke shell.

    Ponsel (<=899px):
        bilah bawah berisi empat tujuan harian (Beranda, Pesanan, Jadwal,
        Buku kas) + tombol "Lainnya" yang membuka lembar berisi SELURUH menu.
        Lima slot membuat label tetap lega dan tidak ada yang terpotong,
        sementara tidak ada tujuan yang hilang.

    Tablet & desktop (>=900px):
        bilah yang sama naik ke atas sebagai baris tautan; tombol "Lainnya"
        disembunyikan dan tujuh tujuan utama ditampilkan langsung.
--}}
@php
    /* Nama kelas ditulis lengkap: `use` di dalam view menyulitkan pembacaan
       dan tidak diperlukan karena hanya dipakai beberapa kali. */
    $pendingCount = 0;
    $unreadCount = 0;
    try {
        $pendingCount = \App\Models\RentalOrderUnit::where('status', 'pending')->count();
        $unreadCount = auth()->user()?->unreadNotifications?->count() ?? 0;
    } catch (\Exception $e) {}

    // Badge menempel pada butir terkait tanpa menduplikasi definisi menu.
    $badges = [
        'Pesanan sewa' => $pendingCount ?: null,
        'Notifikasi' => $unreadCount ?: null,
    ];

    $primaryLabels = ['Dasbor', 'Pesanan sewa', 'Jadwal sewa', 'Buku kas'];
    $topLabels = ['Notifikasi', 'Panduan admin', 'Profil'];

    $primaryItems = [];
    $topItems = [];
    foreach ($primaryLabels as $label) {
        if ($item = \App\Support\AdminNav::find($label)) {
            $primaryItems[] = $item;
        }
    }
    foreach ($topLabels as $label) {
        if ($item = \App\Support\AdminNav::find($label)) {
            $item['wide'] = true;
            $topItems[] = $item;
        }
    }

    $sheetGroups = \App\Support\AdminNav::groups();
@endphp

<nav class="admin-nav" id="admin-nav" aria-label="Navigasi utama admin">
    <div class="admin-nav__inner">
        @foreach($primaryItems as $item)
            @php $isActive = \App\Support\AdminNav::isActive($item); @endphp
            <a href="{{ route($item['route']) }}"
               class="admin-nav__item {{ $isActive ? 'is-active' : '' }}"
               @if($isActive) aria-current="page" @endif
               aria-label="{{ $item['label'] }}">
                <span class="admin-nav__icon">
                    @if($badges[$item['label']] ?? null)
                        <span class="admin-nav__badge">{{ $badges[$item['label']] > 9 ? '9+' : $badges[$item['label']] }}</span>
                    @endif
                    @include('layouts.component._icon', ['name' => $item['icon'], 'size' => 22])
                </span>
                <span class="admin-nav__label">{{ $item['short'] ?? $item['label'] }}</span>
            </a>
        @endforeach

        {{-- Slot kelima: pintu ke seluruh menu. Hanya ada di ponsel. --}}
        <button type="button" class="admin-nav__item admin-nav__more"
                data-admin-more aria-controls="admin-sheet" aria-expanded="false"
                aria-haspopup="dialog" aria-label="Menu lainnya">
            <span class="admin-nav__icon">
                @include('layouts.component._icon', ['name' => 'menu', 'size' => 22])
            </span>
            <span class="admin-nav__label">Lainnya</span>
        </button>

        {{-- Butir yang hanya tampil pada bilah atas (>=900px). --}}
        @foreach($topItems as $item)
            @php $isActive = \App\Support\AdminNav::isActive($item); @endphp
            <a href="{{ route($item['route']) }}"
               class="admin-nav__item admin-nav__item--wide {{ $isActive ? 'is-active' : '' }}"
               @if($isActive) aria-current="page" @endif
               aria-label="{{ $item['label'] }}">
                <span class="admin-nav__icon">
                    @if($badges[$item['label']] ?? null)
                        <span class="admin-nav__badge">{{ $badges[$item['label']] > 9 ? '9+' : $badges[$item['label']] }}</span>
                    @endif
                    @include('layouts.component._icon', ['name' => $item['icon'], 'size' => 22])
                </span>
                <span class="admin-nav__label">{{ $item['short'] ?? $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>

{{-- Lembar "Lainnya": semua tujuan dalam satu tempat, bisa digulir. --}}
<div class="admin-sheet" id="admin-sheet" hidden>
    <button type="button" class="admin-sheet__backdrop" data-admin-sheet-close
            aria-label="Tutup menu lainnya" tabindex="-1"></button>
    <div class="admin-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="admin-sheet-title">
        <div class="admin-sheet__head">
            <div>
                <span class="admin-sheet__eyebrow">Panel admin</span>
                <h2 class="admin-sheet__title" id="admin-sheet-title">Menu lainnya</h2>
            </div>
            <button type="button" class="admin-sheet__close" data-admin-sheet-close aria-label="Tutup menu">&times;</button>
        </div>

        @foreach($sheetGroups as $group)
            <p class="admin-sheet__group">{{ $group['label'] }}</p>
            <div class="admin-sheet__grid">
                @foreach($group['items'] as $item)
                    @php $isActive = \App\Support\AdminNav::isActive($item); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="admin-sheet__link {{ $isActive ? 'is-active' : '' }}"
                       @if($isActive) aria-current="page" @endif>
                        <span class="admin-sheet__icon">
                            @include('layouts.component._icon', ['name' => $item['icon'], 'size' => 20])
                        </span>
                        <span>{{ $item['label'] }}</span>
                        @if($badges[$item['label']] ?? null)
                            <span class="admin-sheet__badge">{{ $badges[$item['label']] > 9 ? '9+' : $badges[$item['label']] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<style>
/* Self-contained (literal values) agar tampil identik di semua halaman admin
   mandiri, dengan padanan tema gelap dan tanpa animasi saat gerak dikurangi. */
.admin-nav {
    position: fixed;
    bottom: 0; left: 0; right: 0;
    z-index: 999;
    background: rgba(255, 250, 248, .97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid #f0dfe1;
    box-shadow: 0 -4px 24px rgba(60, 10, 25, .08);
    padding-bottom: env(safe-area-inset-bottom, 0px);
}
html[data-theme="dark"] .admin-nav {
    background: rgba(29, 23, 27, .97);
    border-top-color: #3a2c32;
    box-shadow: 0 -4px 24px rgba(0, 0, 0, .5);
}
.admin-nav__inner {
    max-width: 620px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    min-height: 64px;
    padding: 0 6px;
}
.admin-nav__item {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    padding: 7px 0;
    text-decoration: none;
    color: #6f5a63;
    position: relative;
    border: 0;
    background: transparent;
    font: inherit;
    cursor: pointer;
    transition: color .2s ease, transform .2s ease;
    -webkit-tap-highlight-color: transparent;
}
html[data-theme="dark"] .admin-nav__item { color: #c9b4bb; }
.admin-nav__item:active { transform: scale(.96); }
.admin-nav__item.is-active { color: #85092d; }
html[data-theme="dark"] .admin-nav__item.is-active { color: #f0b6c4; }
.admin-nav__item.is-active .admin-nav__icon svg { stroke-width: 2.6; }
.admin-nav__icon { position: relative; display: flex; align-items: center; justify-content: center; }
.admin-nav__icon svg { width: 22px; height: 22px; }
.admin-nav__badge {
    position: absolute;
    top: -6px; right: -9px;
    background: #b63050;
    color: #fff;
    font-size: .55rem;
    font-weight: 900;
    min-width: 16px; height: 16px;
    border-radius: 99px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 2px solid #fffaf8;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
html[data-theme="dark"] .admin-nav__badge { border-color: #1d171b; }
/* Huruf biasa (bukan kapital semua) supaya sama dengan bilah bawah shell
   admin dan lebih mudah dibaca di layar kecil. */
.admin-nav__label {
    font-size: .62rem;
    font-weight: 800;
    text-transform: none;
    letter-spacing: 0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
/* Penanda aktif: garis pendek di bawah ikon, sama dengan bilah shell admin. */
.admin-nav__item.is-active::after {
    content: '';
    position: absolute;
    bottom: 4px;
    left: 50%;
    transform: translateX(-50%);
    width: 16px; height: 3px;
    background: currentColor;
    border-radius: 99px;
}
/* Butir tambahan hanya untuk bilah atas; slot "Lainnya" hanya untuk ponsel. */
.admin-nav__item--wide { display: none; }

/* ── Lembar "Lainnya" ───────────────────────────────────────────────────── */
.admin-sheet { position: fixed; inset: 0; z-index: 1200; }
.admin-sheet[hidden] { display: none; }
.admin-sheet__backdrop {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    border: 0; padding: 0; margin: 0;
    background: rgba(35, 8, 18, .52);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    cursor: pointer;
}
.admin-sheet__panel {
    position: absolute; left: 0; right: 0; bottom: 0;
    background: #fffaf8;
    border-radius: 22px 22px 0 0;
    padding: 16px 16px calc(20px + env(safe-area-inset-bottom, 0px));
    box-shadow: 0 -24px 60px -24px rgba(60, 10, 25, .5);
    max-height: 84vh;
    max-height: 84dvh;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    animation: admin-sheet-in .22s ease both;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
}
html[data-theme="dark"] .admin-sheet__panel {
    background: #1d171b;
    box-shadow: 0 -24px 60px -24px rgba(0, 0, 0, .8);
}
@keyframes admin-sheet-in {
    from { transform: translateY(18px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.admin-sheet__head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; margin-bottom: 12px;
}
.admin-sheet__eyebrow {
    font-size: .62rem; font-weight: 800; letter-spacing: .14em;
    text-transform: uppercase; color: #9a7b83;
}
html[data-theme="dark"] .admin-sheet__eyebrow { color: #b39aa1; }
.admin-sheet__title { margin: 2px 0 0; font-size: 1.06rem; font-weight: 800; color: #301722; }
html[data-theme="dark"] .admin-sheet__title { color: #f6ebee; }
.admin-sheet__close {
    width: 40px; height: 40px; flex: 0 0 40px;
    display: grid; place-items: center;
    border-radius: 12px; border: 1px solid #f0dfe1;
    background: #fff; color: #6f5a63;
    font-size: 1.2rem; line-height: 1; cursor: pointer;
}
html[data-theme="dark"] .admin-sheet__close { background: #2a2126; border-color: #3a2c32; color: #e6d3d9; }
.admin-sheet__close:hover { background: #f7eae6; }
html[data-theme="dark"] .admin-sheet__close:hover { background: #33272c; }
.admin-sheet__group {
    margin: 14px 0 8px;
    font-size: .64rem; font-weight: 800; letter-spacing: .12em;
    text-transform: uppercase; color: #9a7b83;
}
html[data-theme="dark"] .admin-sheet__group { color: #b39aa1; }
.admin-sheet__grid {
    display: grid; gap: 8px;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 150px), 1fr));
}
.admin-sheet__link {
    position: relative;
    display: flex; align-items: center; gap: 10px;
    min-height: 50px; padding: 10px 12px;
    border: 1px solid #f0dfe1; border-radius: 14px;
    background: #fff; color: #46323a;
    text-decoration: none; font-size: .84rem; font-weight: 700;
    line-height: 1.3;
}
html[data-theme="dark"] .admin-sheet__link {
    background: #241c21; border-color: #3a2c32; color: #ecdde1;
}
.admin-sheet__link:active { transform: scale(.98); }
.admin-sheet__link.is-active { border-color: #b63050; color: #85092d; background: #fdf1f2; }
html[data-theme="dark"] .admin-sheet__link.is-active { border-color: #d76b86; color: #f0b6c4; background: #2f1e24; }
.admin-sheet__icon { display: flex; flex: 0 0 auto; color: inherit; }
.admin-sheet__badge {
    margin-left: auto;
    background: #b63050; color: #fff;
    font-size: .58rem; font-weight: 900;
    min-width: 18px; height: 18px; padding: 0 5px;
    border-radius: 99px;
    display: inline-flex; align-items: center; justify-content: center;
}
body.admin-sheet-open { overflow: hidden; }

/* ── Tablet & desktop: bilah bawah berubah menjadi bilah atas ────────────── */
@media (min-width: 900px) {
    .admin-nav {
        top: 0; bottom: auto;
        border-top: 0;
        border-bottom: 1px solid #f0dfe1;
        box-shadow: 0 8px 26px -18px rgba(60, 10, 25, .5);
        padding-bottom: 0;
        padding-top: env(safe-area-inset-top, 0px);
    }
    html[data-theme="dark"] .admin-nav {
        border-bottom-color: #3a2c32;
        box-shadow: 0 8px 26px -18px rgba(0, 0, 0, .8);
    }
    .admin-nav__inner {
        max-width: 1120px;
        min-height: 60px;
        gap: 2px;
        padding: 0 20px;
        justify-content: flex-start;
    }
    .admin-nav__item {
        flex: 0 0 auto;
        flex-direction: row;
        gap: 8px;
        padding: 8px 13px;
        border-radius: 11px;
        transition: color .2s ease, background .2s ease;
    }
    .admin-nav__item.is-active {
        background: #f4e8e4;
    }
    html[data-theme="dark"] .admin-nav__item.is-active {
        background: rgba(255, 255, 255, .1);
    }
    .admin-nav__item.is-active::after { display: none; }
    .admin-nav__label {
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
    }
    .admin-nav__icon svg { width: 18px; height: 18px; }
    .admin-nav__badge { top: -4px; right: -6px; }

    /* Bilah atas: tampilkan tujuan tambahan, sembunyikan pintu "Lainnya". */
    .admin-nav__item--wide { display: flex; }
    .admin-nav__more { display: none; }

    /* Ruang untuk bilah atas; ruang bilah bawah tidak lagi diperlukan. */
    body {
        padding-bottom: 40px;
        padding-top: calc(60px + env(safe-area-inset-top, 0px));
    }
}

/* Layar sempit: rapatkan label agar tidak ada yang terpotong di tengah huruf.
   `text-overflow: ellipsis` hanya jaring pengaman bila nama tujuan panjang. */
@media (max-width: 560px) {
    .admin-nav__inner { padding: 0 2px; }
    .admin-nav__item { padding: 6px 0; gap: 2px; }
    .admin-nav__label {
        font-size: .6rem;
        letter-spacing: 0;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .admin-nav__item .admin-nav__icon svg { width: 20px; height: 20px; }
}
@media (max-width: 380px) {
    .admin-nav__label { font-size: .56rem; }
}
@media (prefers-reduced-motion: reduce) {
    .admin-nav__item { transition: none; }
    .admin-nav__item:active { transform: none; }
    .admin-sheet__panel { animation: none; }
    .admin-sheet__link:active { transform: none; }
}
</style>

<script>
/* Buka/tutup lembar "Lainnya". Ditulis mandiri supaya halaman admin lama yang
   tidak memuat bundel JS tetap bisa memakai menu ini. */
(function () {
    if (window.bkAdminSheetReady) return;
    window.bkAdminSheetReady = true;

    var sheet = document.getElementById('admin-sheet');
    var trigger = document.querySelector('[data-admin-more]');
    if (!sheet || !trigger) return;

    var panel = sheet.querySelector('.admin-sheet__panel');
    var lastFocused = null;

    function open() {
        lastFocused = document.activeElement;
        sheet.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        document.body.classList.add('admin-sheet-open');
        var first = sheet.querySelector('.admin-sheet__link');
        if (first) first.focus();
    }

    function close() {
        sheet.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('admin-sheet-open');
        if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    trigger.addEventListener('click', function () {
        if (sheet.hidden) open(); else close();
    });

    sheet.addEventListener('click', function (event) {
        if (event.target.closest('[data-admin-sheet-close]')) close();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !sheet.hidden) close();
    });

    // Layar melebar ke tablet/desktop: lembar tidak lagi relevan.
    window.addEventListener('resize', function () {
        if (!sheet.hidden && window.innerWidth >= 900) close();
    });
})();
</script>
