{{--
    Menu radial — dipakai pelanggan DAN tamu.

    Satu tombol menjadi pusat; tujuan menyebar dalam busur seperti menu radial
    Chrome. Penempatannya menyesuaikan diri: menu duduk di ujung kanan header,
    jadi busur dibuka ke kiri-bawah — arah yang tidak pernah keluar layar,
    bahkan pada ponsel 320 px — dan hanya tampil di ponsel (<=700 px) karena
    di layar lebar tujuan ini sudah tersedia sebagai tautan header.

    Jumlah tujuan berbeda menurut peran (tamu 4, pelanggan 5) dan ada satu slot
    terakhir untuk tombol tema. Karena itu geometri dihitung, bukan ditulis
    tangan: sudut dibagi rata dari 180° ke 79° (batas kanan layar) dan radius
    dipilih agar jarak antarpusat tetap 50 px. Dengan begitu 4, 5, atau 6
    lingkaran 46 px tidak pernah bertumpuk dan tidak pernah terpotong.

    Tanpa JavaScript menu tetap berfungsi: <details> membuka/menutup panel,
    dan CSS menaruh setiap tujuan pada posisinya. GSAP hanya memperhalus
    sebarannya lewat satu variabel (--p), sehingga transform tetap dipegang
    CSS dan tidak bertabrakan dengan :hover/:active.
--}}
@php
    $navActive = $navActive ?? '';
    $cartCount = $cartCount ?? count(session('rental_cart', []));
    $unreadNotif = $unreadNotif ?? 0;
    $isCustomer = auth()->check() && (auth()->user()->role ?? '') === 'customer';

    if ($isCustomer) {
        $radialItems = [
            ['href' => route('cart.index'), 'icon' => 'shopping-bag', 'label' => 'Keranjang'.($cartCount ? ', '.$cartCount.' unit' : ''), 'text' => 'Keranjang', 'badge' => $cartCount, 'current' => $navActive === 'keranjang'],
            ['href' => route('rental.orders.index'), 'icon' => 'clipboard-list', 'label' => 'Pesanan', 'text' => 'Pesanan', 'badge' => 0, 'current' => $navActive === 'pesanan'],
            ['href' => route('customer.notifications'), 'icon' => 'bell', 'label' => 'Notifikasi'.($unreadNotif ? ', '.$unreadNotif.' belum dibaca' : ''), 'text' => 'Notifikasi', 'badge' => $unreadNotif, 'current' => $navActive === 'notif'],
            ['href' => route('customer.addresses.index'), 'icon' => 'address', 'label' => 'Alamat pengiriman', 'text' => 'Alamat', 'badge' => 0, 'current' => request()->routeIs('customer.addresses.*')],
            ['href' => route('customer.help'), 'icon' => 'help-circle', 'label' => 'Bantuan dan FAQ', 'text' => 'Bantuan / FAQ', 'badge' => 0, 'current' => request()->routeIs('customer.help')],
        ];
        $summaryLabel = 'Menu: keranjang, pesanan, notifikasi, alamat, dan bantuan';
    } else {
        $radialItems = [
            ['href' => route('welcome'), 'icon' => 'flower-2', 'label' => 'Katalog', 'text' => 'Katalog', 'badge' => 0, 'current' => request()->routeIs('welcome', 'catalog.show')],
            ['href' => route('cart.index'), 'icon' => 'cart', 'label' => 'Keranjang'.($cartCount ? ', '.$cartCount.' unit' : ''), 'text' => 'Keranjang', 'badge' => $cartCount, 'current' => request()->routeIs('cart.*')],
            ['href' => route('register'), 'icon' => 'add-user', 'label' => 'Daftar akun', 'text' => 'Daftar', 'badge' => 0, 'current' => false],
            ['href' => route('login'), 'icon' => 'account', 'label' => 'Masuk ke akun', 'text' => 'Masuk', 'badge' => 0, 'current' => false],
        ];
        $summaryLabel = 'Menu: katalog, keranjang, daftar, dan masuk';
    }
    if ($isCustomer && $unreadNotif) {
        $summaryLabel .= ', '.$unreadNotif.' notifikasi belum dibaca';
    }

    // Satu slot terakhir selalu untuk tombol tema.
    $slots = count($radialItems) + 1;
    $span = 101;                                   // 180° → 79°: batas kanan layar 320 px
    $step = $slots > 1 ? $span / ($slots - 1) : 0;
    $radius = $step > 0 ? (int) round(25 / sin(deg2rad($step / 2))) : 86;  // jarak antarpusat 50 px
    $radius = max(86, $radius);
    $offsets = [];
    for ($slot = 0; $slot < $slots; $slot++) {
        $angle = deg2rad(180 - ($slot * $step));
        $offsets[$slot] = [(int) round($radius * cos($angle)), (int) round($radius * sin($angle))];
    }

    /* `aria-controls` menunjuk id panel. Id itu tidak boleh tetap: satu halaman
       bisa memuat lebih dari satu busur (mis. header + menu atas), dan id ganda
       membuat pembaca layar melompat ke panel yang salah. Nama mengikuti peran
       supaya tetap dapat diprediksi — sama dengan pratinjau di
       docs/preview/radial.html (bk-radial-panel + bk-radial-panel-tamu). */
    $panelId = $isCustomer ? 'bk-radial-panel' : 'bk-radial-panel-tamu';
@endphp
<details class="customer-radial {{ !empty($inHeader) ? 'bk-header-radial' : '' }}" data-customer-radial>
    <summary class="customer-nav__item {{ in_array($navActive, ['keranjang', 'pesanan', 'notif'], true) || request()->routeIs('customer.help', 'customer.addresses.*') ? 'is-active' : '' }}" aria-controls="{{ $panelId }}" aria-label="{{ $summaryLabel }}">
        <span class="customer-nav__icon customer-radial__trigger">
            <span class="customer-radial__face customer-radial__face--menu">@include('layouts.component._icon', ['name' => 'menu', 'size' => 24])</span>
            <span class="customer-radial__face customer-radial__face--close">@include('layouts.component._icon', ['name' => 'close', 'size' => 24])</span>
            @if($isCustomer && $unreadNotif)<span class="customer-nav__badge" aria-hidden="true">{{ $unreadNotif > 99 ? '99+' : $unreadNotif }}</span>@endif
        </span>
        <span class="customer-nav__label">Menu</span>
    </summary>
    <div class="customer-radial__panel" id="{{ $panelId }}" role="group" aria-label="Menu {{ $isCustomer ? 'pelanggan' : 'pengunjung' }}">
        <div class="customer-radial__heading"><strong>Menu {{ $isCustomer ? 'pelanggan' : 'pengunjung' }}</strong><span>Pilih tujuan</span></div>
        <div class="customer-radial__links">
            {{-- Jejak halus di belakang tombol: memberi kedalaman tanpa kartu. --}}
            <svg class="customer-radial__goo" viewBox="0 0 320 150" preserveAspectRatio="none" aria-hidden="true" focusable="false"><defs><filter id="bk-menu-goo" x="-20%" y="-30%" width="140%" height="160%"><feGaussianBlur in="SourceGraphic" stdDeviation="6" result="blur"/><feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 14 -5"/></filter></defs><g filter="url(#bk-menu-goo)" fill="#ead4d4"><circle cx="42" cy="86" r="26"/><circle cx="118" cy="36" r="26"/><circle cx="202" cy="36" r="26"/><circle cx="278" cy="86" r="26"/><path d="M42 86 Q160 8 278 86 L160 125 Z"/></g></svg>
            @foreach($radialItems as $slot => $item)
                <a href="{{ $item['href'] }}" class="customer-radial__item" style="--rx:{{ $offsets[$slot][0] }}px;--ry:{{ $offsets[$slot][1] }}px" @if($item['current']) aria-current="page" @endif aria-label="{{ $item['label'] }}">
                    <span class="customer-radial__glyph" aria-hidden="true">@include('layouts.component._icon', ['name' => $item['icon'], 'size' => 20])</span>
                    @if($item['badge'])<span class="customer-radial__badge" aria-hidden="true">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>@endif
                    <span class="customer-radial__text">{{ $item['text'] }}</span>
                </a>
            @endforeach
            {{-- Slot tema: di ponsel, berkah-ui-kit.js memindahkan tombol tema
                 ke sini sehingga hanya ada satu tombol (bukan dua). --}}
            <span class="customer-radial__item customer-radial__item--theme" data-bk-theme-host="radial" style="--rx:{{ $offsets[$slots - 1][0] }}px;--ry:{{ $offsets[$slots - 1][1] }}px"></span>
        </div>
    </div>
</details>
@once
<style>
/* ── Radial: busur tujuan ────────────────────────────────────────────────
   Posisi tiap tujuan datang dari server (--rx/--ry pada style), jadi
   jumlah tujuan boleh berubah tanpa menyentuh CSS. --p (0..1) menggerakkannya
   dari pusat; GSAP menganimasikan --p saja — transform tetap milik CSS. */

/* ── Kapan tombol busur muncul ───────────────────────────────────────────
   Aturan ini dulu hanya ada di _navbar_customer (khusus pelanggan). Karena
   halaman tamu memakai partial yang sama tanpa partial itu, tombol tamu
   tampil di segala lebar: <details> tanpa position:relative membuat
   panelnya bertumpu pada kotak selebar header, sehingga busur melewati
   wordmark (sempit) dan mengambang di atas hero (lebar). Sekarang aturannya
   ikut partial ini supaya berlaku untuk semua peran: di ponsel saja, dan
   selalu menjadi titik tumpu bagi panelnya. */
.bk-header-radial{display:none;position:relative;flex:0 0 auto}
.bk-header.is-compact .bk-header-radial>summary{width:44px;height:44px}
/* Ambangnya sama dengan ambang nav lebar: di bawah 880 px tautan header
   disembunyikan (lihat berkah-experience.css), jadi busur inilah satu-satunya
   jalan ke Pesanan/Bantuan. Dulu ambangnya 700 px, dan pada tablet 701–880 px
   tidak ada menu sama sekali: tautan header hilang, tombol busur juga. */
@media(max-width:880px){.bk-header-radial{display:block}}

html[data-theme] .bk-header-radial .customer-radial__panel{position:absolute;top:50%;right:50%;width:0;height:0;padding:0;margin:0;border:0;border-radius:0;background:none;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none;transform:translate(50%,-50%);overflow:visible;max-height:none;z-index:1200}
.bk-header-radial .customer-radial__heading{display:none}
.bk-header-radial .customer-radial__links{position:relative;display:block;width:0;height:0;margin:0;padding:0;overflow:visible}
/* Tidak ada cincin atau bidang di belakang busur: tujuan melayang langsung
   di atas halaman, jadi tidak ada yang terbaca sebagai “latar” menu. */
.bk-header-radial .customer-radial__goo{display:none}

/* Satu tujuan: lingkaran kaca 46 px (>44 px sasaran sentuh). */
.bk-header-radial .customer-radial__item{
    position:absolute;left:0;top:0;display:grid;place-items:center;
    width:46px;height:46px;min-width:0;min-height:0;margin:0;padding:0;
    border:1px solid rgba(234,217,214,.9);border-radius:50%;
    background:rgba(255,252,249,.96);color:#6b3a45;text-decoration:none;
    box-shadow:0 10px 24px rgba(65,22,32,.18);
    backdrop-filter:blur(14px) saturate(150%);-webkit-backdrop-filter:blur(14px) saturate(150%);
    font-size:0;line-height:1;text-align:center;
    --p:0;--hover:1;
    transform:translate(-50%,-50%) translate(calc(var(--rx,0px) * var(--p)), calc(var(--ry,0px) * var(--p))) scale(calc((.42 + .58 * var(--p)) * var(--hover)));
    opacity:var(--p);
    transition:transform .42s cubic-bezier(.34,1.4,.64,1),opacity .22s ease,background-color .18s ease,color .18s ease,border-color .18s ease;
    -webkit-tap-highlight-color:transparent;
}
.customer-radial[open] .customer-radial__item{--p:1}
.bk-header-radial .customer-radial__item:hover,
.bk-header-radial .customer-radial__item:focus-visible,
.bk-header-radial .customer-radial__item[aria-current]{--hover:1.08;background:#85092d;border-color:#85092d;color:#fff}
.bk-header-radial .customer-radial__item:focus-visible{outline:3px solid #bc5b75;outline-offset:3px}
.bk-header-radial .customer-radial__item:active{--hover:.96}

/* Slot tema: bingkai transparan; tombol tema di dalamnya yang menjadi lingkaran. */
.bk-header-radial .customer-radial__item--theme{background:none;border:0;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none}
.bk-header-radial .customer-radial__item--theme:empty{display:none}
.bk-header-radial .customer-radial__item--theme .bk-theme-btn{
    width:46px;height:46px;border-radius:50%;padding:0;
    border:1px solid rgba(234,217,214,.9);background:rgba(255,252,249,.96);color:#6b3a45;
    box-shadow:0 10px 24px rgba(65,22,32,.18);
    backdrop-filter:blur(14px) saturate(150%);-webkit-backdrop-filter:blur(14px) saturate(150%);
}
.bk-header-radial .customer-radial__item--theme .bk-theme-btn:hover{transform:none;background:#85092d;color:#fff;border-color:#85092d}

html[data-theme="dark"] .bk-header-radial .customer-radial__item{background:rgba(38,30,34,.92);border-color:rgba(124,94,104,.55);color:#f6ece8;box-shadow:0 10px 26px rgba(0,0,0,.5)}
html[data-theme="dark"] .bk-header-radial .customer-radial__item:hover,
html[data-theme="dark"] .bk-header-radial .customer-radial__item:focus-visible,
html[data-theme="dark"] .bk-header-radial .customer-radial__item[aria-current]{background:#f3a8bb;border-color:#f3a8bb;color:#2a1219}
html[data-theme="dark"] .bk-header-radial .customer-radial__item--theme{background:none;border:0;box-shadow:none}
html[data-theme="dark"] .bk-header-radial .customer-radial__item--theme .bk-theme-btn{background:rgba(38,30,34,.92);border-color:rgba(124,94,104,.55);color:#f3a8bb;box-shadow:0 10px 26px rgba(0,0,0,.5)}
html[data-theme="dark"] .bk-header-radial .customer-radial__item--theme .bk-theme-btn:hover{background:#f3a8bb;border-color:#f3a8bb;color:#2a1219}

/* Ikon di dalam lingkaran: latar lingkaran sudah dipegang item. */
html[data-theme] .bk-header-radial .customer-radial__glyph{width:auto;height:auto;background:transparent;border:0;box-shadow:none;color:inherit;border-radius:0}
html[data-theme] .bk-header-radial .customer-radial__glyph svg{display:block;width:20px;height:20px}

/* Label teks tetap ada untuk pembaca layar dan pencarian, tidak dicetak. */
.bk-header-radial .customer-radial__text{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip-path:inset(50%);white-space:nowrap;border:0}

/* Lencana jumlah: angka kecil di sudut kanan atas (gaya Chrome). */
/* Lencana: --bp (0..1, boleh melewati 1 karena back.out) memantulkan angkanya,
   --p mengikuti busur. Dua-duanya custom property, jadi GSAP tidak pernah
   menulis transform inline yang menempel dan mengunci keadaan. */
.customer-radial__badge{position:absolute;top:-5px;right:-6px;display:grid;place-items:center;min-width:20px;height:20px;padding:0 4px;border:2px solid #fffaf8;border-radius:99px;background:#85092d;color:#fff;font-size:var(--bk-fs-2xs);font-weight:800;line-height:1;opacity:calc(var(--p) * var(--bp,1));transform:scale(calc((.4 + .6 * var(--p)) * (.3 + .7 * var(--bp,1))));pointer-events:none;transition:none}
html[data-theme="dark"] .customer-radial__badge{border-color:#201820;background:#f3a8bb;color:#2a1219}

/* Cincin fokus tombol pusat. Tata letak katalog sudah punya aturan global
   `summary:focus-visible`, tetapi halaman pelanggan tidak — tanpa ini tombol
   menu tidak punya penanda fokus sama sekali di sana. */
.bk-header-radial>summary:focus-visible{outline:3px solid #bc5b75;outline-offset:3px;border-radius:50%}
html[data-theme="dark"] .bk-header-radial>summary:focus-visible{outline-color:#f3a8bb}
html[data-theme="dark"] .bk-header-radial .customer-radial__item:focus-visible{outline-color:#f3a8bb}
html[data-theme="dark"] .bk-header-radial .customer-radial__item--theme .bk-theme-btn:focus-visible{outline-color:#f3a8bb}

/* Kontras lebih tinggi: garis lingkaran ditebalkan, cincin fokus diperlebar —
   sejalan dengan aturan `prefers-contrast: more` di berkah-ui-kit.css. */
@media (prefers-contrast: more){
    .bk-header-radial>summary,
    .bk-header-radial .customer-radial__item,
    .bk-header-radial .customer-radial__item--theme .bk-theme-btn{border-width:2px}
    .bk-header-radial>summary:focus-visible,
    .bk-header-radial .customer-radial__item:focus-visible{outline-width:4px}
}

/* Kontras paksa (Windows High Contrast): latar kaca, bayangan, dan border
   berwarna merek dihapus peramban, sehingga tombol dan tujuan bisa menyatu
   dengan halaman. Di sini semuanya digambar dengan warna sistem, dan tujuan
   yang aktif/terfokus dibalik memakai Highlight. */
@media (forced-colors: active){
    .bk-header-radial>summary,
    .bk-header-radial .customer-radial__item,
    .bk-header-radial .customer-radial__item--theme .bk-theme-btn{
        forced-color-adjust: none;
        background: ButtonFace; color: ButtonText;
        border: 1px solid ButtonBorder; box-shadow: none;
    }
    .bk-header-radial[open]>summary,
    .bk-header-radial .customer-radial__item:hover,
    .bk-header-radial .customer-radial__item:focus-visible,
    .bk-header-radial .customer-radial__item[aria-current],
    .bk-header-radial .customer-radial__item--theme .bk-theme-btn:hover{
        background: Highlight; color: HighlightText; border-color: Highlight;
    }
    .bk-header-radial>summary::after{border-color: Highlight}
    .customer-radial__badge{
        forced-color-adjust: none;
        background: Highlight; color: HighlightText; border-color: Canvas;
    }
}

/* Tombol pusat: dua ikon bertukar (menu ⇄ tutup) dan cincin tipis 58 px —
   satu-satunya hiasan yang tersisa setelah cincin orbit 244 px dilepas. */
.bk-header-radial>summary{position:relative;display:grid;place-items:center;width:48px;height:48px;padding:0;border:1px solid #ead9d6;border-radius:50%;background:rgba(255,250,248,.94);color:#85092d;box-shadow:0 8px 20px rgba(84,5,31,.14);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);transition:background-color .2s ease,color .2s ease,border-color .2s ease}
.bk-header-radial>summary::after{content:"";position:absolute;inset:-5px;border:1px solid rgba(133,9,45,.22);border-radius:50%;opacity:0;transform:scale(.86);pointer-events:none;transition:opacity .24s ease,transform .36s cubic-bezier(.34,1.4,.64,1)}
.bk-header-radial[open]>summary::after{opacity:1;transform:scale(1)}
/* Pil ringkas hanya setinggi 44+8 px; cincin 5 px di luarnya akan menyentuh
   tepi pil (terlihat seperti tombol yang menembus header). Di mode ringkas
   cincinnya dirapatkan 2 px supaya tetap berada di dalam pil. */
.bk-header.is-compact .bk-header-radial>summary::after{inset:-3px}
/* Tombol busur tidak lagi membawa ukuran sendiri: kotaknya mengikuti
   <summary> (48 px, 44 px saat header ringkas) lewat empat sisi di bawah,
   jadi tombol tamu tidak melebar keluar pil. Yang tersisa di sini hanya
   pelepasan latar dan garis milik .customer-nav__icon. */
html[data-theme] .bk-header-radial>summary .customer-nav__icon.customer-radial__trigger{background:transparent;border:0}
.bk-header-radial>summary .customer-nav__label{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
/* Titik tumpu ikon. Dulu panahnya `inset:0` di dalam grid kecil, dan pada
   peramban tertentu ikon garis-3 terdorong ke tepi kanan lingkaran sehingga
   terlihat tidak simetris. Sekarang kotak tombol itu sendiri yang menjadi
   kotak isi: selebar/ setinggi <summary>, dipasang absolut dari empat sisi,
   lalu ikon di tengah dengan flex. Tidak ada lagi jalur tata letak yang bisa
   menggesernya. */
.bk-header-radial .customer-radial__trigger{position:absolute;top:0;right:0;bottom:0;left:0;display:flex;align-items:center;justify-content:center;width:auto;height:auto;margin:0;padding:0}
.bk-header-radial .customer-radial__face{position:absolute;top:0;right:0;bottom:0;left:0;display:flex;align-items:center;justify-content:center;margin:0;padding:0;transition:opacity .18s ease,transform .3s cubic-bezier(.34,1.4,.64,1)}
/* Ikon digambar pada ukuran aslinya (24 px, kelipatan bulat dari viewBox 24)
   supaya garis 2 px jatuh di piksel bulat: tiga garis jadi sama panjang,
   sama jarak, dan sama tebal. Pada 22 px skalanya 0,917 sehingga garis
   tengah terlihat 1 px lebih panjang — itulah "tidak simetris" yang tampak. */
.bk-header-radial>summary svg{display:block;flex:none;margin:0;width:24px;height:24px}
.bk-header-radial .customer-radial__face--close{opacity:0;transform:rotate(-90deg) scale(.55)}
.bk-header-radial[open] .customer-radial__face--menu{opacity:0;transform:rotate(90deg) scale(.55)}
.bk-header-radial[open] .customer-radial__face--close{opacity:1;transform:rotate(0) scale(1)}
html[data-theme="dark"] .bk-header-radial>summary{background:rgba(38,30,34,.94);border-color:rgba(124,94,104,.55);color:#f3a8bb}
html[data-theme="dark"] .bk-header-radial[open]>summary,
html[data-theme="dark"] .bk-header-radial>summary:hover{background:#33222a;color:#f3a8bb}

/* GSAP memegang nilainya; transisi CSS dimatikan agar tidak berebut. */
.customer-radial.is-gsap .customer-radial__item{transition:background-color .18s ease,color .18s ease,border-color .18s ease}

@media (prefers-reduced-motion: reduce){
    .bk-header-radial .customer-radial__item,
    .bk-header-radial>summary::after,
    .customer-radial__badge,
    .bk-header-radial .customer-radial__face{transition:none}
    .bk-header-radial .customer-radial__item{--hover:1}
}
</style>
@php
    $radialJs = public_path('js/berkah-radial.js');
    $radialSrc = asset('js/berkah-radial.js') . (file_exists($radialJs) ? '?v=' . filemtime($radialJs) : '');
@endphp
<script src="{{ $radialSrc }}" defer></script>
@endonce
