{{--
    Menu radial pelanggan.

    Satu tombol menjadi pusat; tujuan menyebar dalam busur seperti menu radial
    Chrome. Penempatannya menyesuaikan diri: menu duduk di ujung kanan header,
    jadi busur dibuka ke kiri-bawah — arah yang tidak pernah keluar layar,
    bahkan pada ponsel 320 px — dan hanya tampil di ponsel (<=700 px) karena
    di layar lebar tujuan ini sudah tersedia sebagai tautan header.

    Tanpa JavaScript menu tetap berfungsi: <details> membuka/menutup panel,
    dan CSS menaruh setiap tujuan pada posisinya. GSAP hanya memperhalus
    sebarannya lewat satu variabel (--p), sehingga transform tetap dipegang
    CSS dan tidak bertabrakan dengan :hover/:active.
--}}
@php
    $navActive = $navActive ?? '';
    $cartCount = $cartCount ?? count(session('rental_cart', []));
    $unreadNotif = $unreadNotif ?? 0;
@endphp
<details class="customer-radial {{ !empty($inHeader) ? 'bk-header-radial' : '' }}" data-customer-radial>
    <summary class="customer-nav__item {{ in_array($navActive, ['keranjang', 'pesanan', 'notif'], true) || request()->routeIs('customer.help') ? 'is-active' : '' }}" aria-label="Menu: keranjang, pesanan, notifikasi, dan bantuan{{ $unreadNotif ? ', '.$unreadNotif.' notifikasi belum dibaca' : '' }}">
        <span class="customer-nav__icon customer-radial__trigger">
            <span class="customer-radial__face customer-radial__face--menu">@include('layouts.component._icon', ['name' => 'menu', 'size' => 24])</span>
            <span class="customer-radial__face customer-radial__face--close">@include('layouts.component._icon', ['name' => 'close', 'size' => 24])</span>
            @if($unreadNotif)<span class="customer-nav__badge" aria-hidden="true">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>@endif
        </span>
        <span class="customer-nav__label">Menu</span>
    </summary>
    <div class="customer-radial__panel" aria-label="Menu pelanggan">
        <div class="customer-radial__heading"><strong>Menu pelanggan</strong><span>Pilih tujuan</span></div>
        <div class="customer-radial__links">
            {{-- Jejak halus di belakang tombol: memberi kedalaman tanpa kartu. --}}
            <svg class="customer-radial__goo" viewBox="0 0 320 150" preserveAspectRatio="none" aria-hidden="true" focusable="false"><defs><filter id="bk-menu-goo" x="-20%" y="-30%" width="140%" height="160%"><feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur"/><feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 14 -5"/></filter></defs><g filter="url(#bk-menu-goo)" fill="#ead4d4"><circle cx="42" cy="86" r="26"/><circle cx="118" cy="36" r="26"/><circle cx="202" cy="36" r="26"/><circle cx="278" cy="86" r="26"/><path d="M42 86 Q160 8 278 86 L160 125 Z"/></g></svg>
            <a href="{{ route('cart.index') }}" @if($navActive === 'keranjang') aria-current="page" @endif aria-label="Keranjang{{ $cartCount ? ', '.$cartCount.' unit' : '' }}">
                <span class="customer-radial__glyph" aria-hidden="true">@include('layouts.component._icon', ['name' => 'shopping-bag', 'size' => 20])</span>
                @if($cartCount)<span class="customer-radial__badge" aria-hidden="true">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>@endif
                <span class="customer-radial__text">Keranjang @if($cartCount)<small>{{ $cartCount }} unit</small>@endif</span>
            </a>
            <a href="{{ route('rental.orders.index') }}" @if($navActive === 'pesanan') aria-current="page" @endif aria-label="Pesanan">
                <span class="customer-radial__glyph" aria-hidden="true">@include('layouts.component._icon', ['name' => 'clipboard-list', 'size' => 20])</span>
                <span class="customer-radial__text">Pesanan</span>
            </a>
            <a href="{{ route('customer.notifications') }}" @if($navActive === 'notif') aria-current="page" @endif aria-label="Notifikasi{{ $unreadNotif ? ', '.$unreadNotif.' belum dibaca' : '' }}">
                <span class="customer-radial__glyph" aria-hidden="true">@include('layouts.component._icon', ['name' => 'bell', 'size' => 20])</span>
                @if($unreadNotif)<span class="customer-radial__badge" aria-hidden="true">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>@endif
                <span class="customer-radial__text">Notifikasi @if($unreadNotif)<small>{{ $unreadNotif }} baru</small>@endif</span>
            </a>
            <a href="{{ route('customer.help') }}" @if(request()->routeIs('customer.help')) aria-current="page" @endif aria-label="Bantuan dan FAQ">
                <span class="customer-radial__glyph" aria-hidden="true">@include('layouts.component._icon', ['name' => 'help-circle', 'size' => 20])</span>
                <span class="customer-radial__text">Bantuan / FAQ</span>
            </a>
        </div>
    </div>
</details>
@once
<style>
/* ── Radial: busur tujuan ────────────────────────────────────────────────
   Busur dibuka ke kiri-bawah dari tombol (yang duduk di ujung kanan header).
   Empat tujuan berjarak 46 px dengan sudut 32° pada radius 100 px, jadi
   tidak ada yang bertumpuk dan tidak ada yang keluar layar 320 px.
   Posisi tiap tujuan disimpan sebagai --rx/--ry; --p (0..1) menggerakkannya
   dari pusat. GSAP menganimasikan --p saja — transform tetap milik CSS. */
html[data-theme] .bk-header-radial .customer-radial__panel{position:absolute;top:50%;right:50%;width:0;height:0;padding:0;margin:0;border:0;border-radius:0;background:none;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none;transform:translate(50%,-50%);overflow:visible;max-height:none;z-index:1200}
.bk-header-radial .customer-radial__heading{display:none}
.bk-header-radial .customer-radial__links{position:relative;display:block;width:0;height:0;margin:0;padding:0;overflow:visible}
.bk-header-radial .customer-radial__links::before{content:"";position:absolute;left:0;top:0;width:224px;height:224px;transform:translate(-50%,-50%) scale(.72);border:1px solid rgba(133,9,45,.16);border-radius:50%;opacity:0;pointer-events:none;transition:opacity .26s ease,transform .5s cubic-bezier(.34,1.4,.64,1)}
.bk-header-radial[open] .customer-radial__links::before{opacity:1;transform:translate(-50%,-50%) scale(1)}
.bk-header-radial .customer-radial__goo{position:absolute;left:0;top:0;width:238px;height:238px;margin:-119px 0 0 -119px;opacity:.16;pointer-events:none;transition:opacity .3s ease}
.bk-header-radial[open] .customer-radial__goo{opacity:.16}

/* Tombol tujuan: lingkaran kaca 46 px (>44 px sasaran sentuh). */
.bk-header-radial .customer-radial__links a{
    position:absolute;left:0;top:0;display:grid;place-items:center;
    width:46px;height:46px;min-width:0;min-height:0;margin:0;padding:0;
    border:1px solid rgba(234,217,214,.9);border-radius:50%;
    background:rgba(255,252,249,.96);color:#6b3a45;text-decoration:none;
    box-shadow:0 10px 24px rgba(65,22,32,.18);
    backdrop-filter:blur(14px) saturate(150%);-webkit-backdrop-filter:blur(14px) saturate(150%);
    font-size:0;line-height:1;text-align:center;
    --p:0;--rx:0px;--ry:0px;--hover:1;
    transform:translate(-50%,-50%) translate(calc(var(--rx) * var(--p)), calc(var(--ry) * var(--p))) scale(calc((.42 + .58 * var(--p)) * var(--hover)));
    opacity:var(--p);
    transition:transform .42s cubic-bezier(.34,1.4,.64,1),opacity .22s ease,background-color .18s ease,color .18s ease,border-color .18s ease;
    -webkit-tap-highlight-color:transparent;
}
/* Sudut busur: 168°, 136°, 104°, 74° pada radius 100 px. */
.bk-header-radial .customer-radial__links a:nth-of-type(1){--rx:-98px;--ry:21px}
.bk-header-radial .customer-radial__links a:nth-of-type(2){--rx:-72px;--ry:70px}
.bk-header-radial .customer-radial__links a:nth-of-type(3){--rx:-24px;--ry:97px}
.bk-header-radial .customer-radial__links a:nth-of-type(4){--rx:28px;--ry:96px}
.customer-radial[open] .customer-radial__links a{--p:1}
.bk-header-radial .customer-radial__links a:hover,
.bk-header-radial .customer-radial__links a:focus-visible,
.bk-header-radial .customer-radial__links a[aria-current]{--hover:1.08;background:#85092d;border-color:#85092d;color:#fff}
.bk-header-radial .customer-radial__links a:focus-visible{outline:3px solid #bc5b75;outline-offset:3px}
.bk-header-radial .customer-radial__links a:active{--hover:.96}

html[data-theme="dark"] .bk-header-radial .customer-radial__links a{background:rgba(38,30,34,.92);border-color:rgba(124,94,104,.55);color:#f6ece8;box-shadow:0 10px 26px rgba(0,0,0,.5)}
html[data-theme="dark"] .bk-header-radial .customer-radial__links a:hover,
html[data-theme="dark"] .bk-header-radial .customer-radial__links a:focus-visible,
html[data-theme="dark"] .bk-header-radial .customer-radial__links a[aria-current]{background:#f3a8bb;border-color:#f3a8bb;color:#2a1219}
html[data-theme="dark"] .bk-header-radial .customer-radial__links::before{border-color:rgba(243,168,187,.22)}

/* Ikon di dalam lingkaran: latar lingkaran sudah dipegang <a>. */
html[data-theme] .bk-header-radial .customer-radial__glyph{width:auto;height:auto;background:transparent;border:0;box-shadow:none;color:inherit;border-radius:0}
html[data-theme] .bk-header-radial .customer-radial__glyph svg{display:block;width:20px;height:20px}

/* Label teks tetap ada untuk pembaca layar dan pencarian, tidak dicetak. */
.bk-header-radial .customer-radial__text{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip-path:inset(50%);white-space:nowrap;border:0}

/* Lencana jumlah: angka kecil di sudut kanan atas (gaya Chrome). */
.customer-radial__badge{position:absolute;top:-5px;right:-6px;display:grid;place-items:center;min-width:20px;height:20px;padding:0 4px;border:2px solid #fffaf8;border-radius:99px;background:#85092d;color:#fff;font-size:var(--bk-fs-2xs);font-weight:800;line-height:1;opacity:var(--p);transform:scale(calc(.4 + .6 * var(--p)));pointer-events:none}
html[data-theme="dark"] .customer-radial__badge{border-color:#201820;background:#f3a8bb;color:#2a1219}html[data-theme="dark"] .customer-radial__goo g{fill:#f3a8bb;opacity:.55}

/* Tombol pusat: dua ikon bertukar (menu ⇄ tutup) dan cincin orbit. */
.bk-header-radial>summary{position:relative;display:grid;place-items:center;width:48px;height:48px;padding:0;border:1px solid #ead9d6;border-radius:50%;background:rgba(255,250,248,.94);color:#85092d;box-shadow:0 8px 20px rgba(84,5,31,.14);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);transition:background-color .2s ease,color .2s ease,border-color .2s ease}
.bk-header-radial>summary::after{content:"";position:absolute;inset:-5px;border:1px solid rgba(133,9,45,.22);border-radius:50%;opacity:0;transform:scale(.86);pointer-events:none;transition:opacity .24s ease,transform .36s cubic-bezier(.34,1.4,.64,1)}
.bk-header-radial[open]>summary::after{opacity:1;transform:scale(1)}
html[data-theme] .bk-header-radial>summary .customer-nav__icon.customer-radial__trigger{width:48px;height:48px;background:transparent;border:0}
.bk-header-radial>summary .customer-nav__label{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
.bk-header-radial .customer-radial__trigger{position:relative;display:grid;place-items:center}
.bk-header-radial .customer-radial__face{position:absolute;inset:0;display:grid;place-items:center;transition:opacity .18s ease,transform .3s cubic-bezier(.34,1.4,.64,1)}
.bk-header-radial .customer-radial__face--close{opacity:0;transform:rotate(-90deg) scale(.55)}
.bk-header-radial[open] .customer-radial__face--menu{opacity:0;transform:rotate(90deg) scale(.55)}
.bk-header-radial[open] .customer-radial__face--close{opacity:1;transform:rotate(0) scale(1)}
html[data-theme="dark"] .bk-header-radial>summary{background:rgba(38,30,34,.94);border-color:rgba(124,94,104,.55);color:#f3a8bb}
html[data-theme="dark"] .bk-header-radial[open]>summary,
html[data-theme="dark"] .bk-header-radial>summary:hover{background:#33222a;color:#f3a8bb}

/* GSAP memegang nilainya; transisi CSS dimatikan agar tidak berebut. */
.customer-radial.is-gsap .customer-radial__links a{transition:background-color .18s ease,color .18s ease,border-color .18s ease}

@media (prefers-reduced-motion: reduce){
    .bk-header-radial .customer-radial__links a,
    .bk-header-radial .customer-radial__links::before,
    .bk-header-radial>summary::after,
    .customer-radial__badge,
    .bk-header-radial .customer-radial__face{transition:none}
    .bk-header-radial .customer-radial__links a{--hover:1}
}
</style>
@php
    $radialJs = public_path('js/berkah-radial.js');
    $radialSrc = asset('js/berkah-radial.js') . (file_exists($radialJs) ? '?v=' . filemtime($radialJs) : '');
@endphp
<script src="{{ $radialSrc }}" defer></script>
@endonce
