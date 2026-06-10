<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Panduan Admin – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · ADMIN · PANDUAN
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens
   emitted by layouts.component._tokens (mirrors tailwind.config.js).
═══════════════════════════════════════════════════════════ */
:root {
    /* Page-local aliases — only reference canonical tokens (no literals) */
    --ink:        var(--brand-900);
    --ink-mid:    var(--brand-500);
    --ink-muted:  var(--surface-400);
    --line:       var(--surface-200);
    --line-soft:  var(--surface-100);
    --font:       'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
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
.wrap { max-width: 520px; margin: 0 auto; padding: 0 var(--space-4); }

/* ── Entrance animation (pure CSS — transform/opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ HEADER */
.appbar {
    max-width: 520px; margin: 0 auto;
    display: flex; align-items: flex-start; gap: var(--space-3);
    padding: max(env(safe-area-inset-top, 0px), var(--space-3)) var(--space-4) var(--space-4);
}
.icon-btn {
    width: var(--space-11); height: var(--space-11); border-radius: var(--radius-pill);
    background: var(--brand-100); border: 1px solid var(--surface-200);
    display: flex; align-items: center; justify-content: center;
    color: var(--brand-900); text-decoration: none; flex-shrink: 0;
    transition: transform .12s ease, background .15s ease;
}
.icon-btn:active { transform: scale(.94); background: var(--surface-100); }
.appbar__head { min-width: 0; }
.appbar__title { font-size: 1.15rem; font-weight: 800; color: var(--brand-900); line-height: 1.1; }
.appbar__sub { font-size: .76rem; font-weight: 700; color: var(--ink-muted); margin-top: var(--space-1); line-height: 1.4; }

/* ═══════════════════════════════════════════════ TABS */
.tab-row { display: flex; gap: var(--space-2); overflow-x: auto; padding-bottom: var(--space-2); margin-bottom: var(--space-4); scrollbar-width: none; }
.tab-row::-webkit-scrollbar { display: none; }
.tab-btn {
    white-space: nowrap; min-height: var(--space-10); padding: 0 var(--space-4);
    border-radius: var(--radius-pill); border: 1px solid var(--surface-200);
    background: var(--surface-raised); font-family: var(--font);
    font-size: .78rem; font-weight: 800; color: var(--ink-muted); cursor: pointer;
    transition: transform .12s ease, background .15s ease, color .15s ease;
}
.tab-btn:active { transform: scale(.96); }
.tab-btn.active { background: var(--brand-500); color: var(--surface-raised); border-color: var(--brand-500); }
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ═══════════════════════════════════════════════ SECTIONS / CARDS */
.section-title {
    display: flex; align-items: center; gap: var(--space-2);
    font-weight: 800; font-size: .92rem; color: var(--ink);
    margin: var(--space-4) 0 var(--space-3);
}
.section-title:first-child { margin-top: 0; }
.section-title svg { color: var(--brand-500); }
.card {
    background: var(--surface-raised); border: 1px solid var(--surface-200);
    border-radius: var(--radius-card); padding: var(--space-4);
    margin-bottom: var(--space-3); box-shadow: var(--shadow-card);
}
.card h4 { font-weight: 800; font-size: .88rem; margin-bottom: var(--space-2); color: var(--ink); }
.card p, .card li { font-size: .82rem; font-weight: 700; color: var(--ink-muted); line-height: 1.7; }
.card ul { padding-left: var(--space-4); margin-top: var(--space-1); }
.card ul li { margin-bottom: var(--space-1); }
.card strong { color: var(--ink); }
.card-tip { background: color-mix(in srgb, var(--success-500) 10%, var(--surface-raised)); border-color: color-mix(in srgb, var(--success-500) 35%, var(--surface-200)); }
.card-tip h4 { color: var(--success-500); }
.card-warning { background: color-mix(in srgb, var(--warning-500) 12%, var(--surface-raised)); border-color: color-mix(in srgb, var(--warning-500) 35%, var(--surface-200)); }
.card-warning h4 { color: var(--warning-500); }

/* ── Flow timeline ── */
.flow { display: flex; flex-direction: column; gap: 0; margin: var(--space-2) 0; }
.flow-item { display: flex; align-items: flex-start; gap: var(--space-3); position: relative; padding-bottom: var(--space-4); }
.flow-item:last-child { padding-bottom: 0; }
.flow-dot { width: 10px; height: 10px; border-radius: var(--radius-pill); background: var(--brand-500); flex-shrink: 0; margin-top: var(--space-1); position: relative; z-index: 1; }
.flow-item:not(:last-child) .flow-dot::after { content: ''; position: absolute; top: 10px; left: 4px; width: 2px; height: calc(100% + var(--space-2)); background: var(--surface-200); }
.flow-text { flex: 1; min-width: 0; }
.flow-label { font-weight: 800; font-size: .82rem; color: var(--ink); }
.flow-desc { font-size: .74rem; font-weight: 700; color: var(--ink-muted); margin-top: 2px; line-height: 1.6; }
.flow-dot--done { background: var(--success-500); }

/* ── FAQ accordion ── */
.faq-card {
    background: var(--surface-raised); border: 1px solid var(--surface-200);
    border-radius: var(--radius-card); overflow: hidden;
    margin-bottom: var(--space-3); box-shadow: var(--shadow-card);
}
.faq-item { border-bottom: 1px solid var(--surface-100); }
.faq-item:last-child { border-bottom: none; }
.faq-q {
    display: flex; align-items: center; justify-content: space-between; gap: var(--space-2);
    padding: var(--space-3) var(--space-4); cursor: pointer;
    font-weight: 800; font-size: .84rem; color: var(--ink); user-select: none;
}
.faq-q .faq-chevron { flex-shrink: 0; color: var(--brand-500); display: inline-flex; transition: transform .2s ease; }
.faq-item.open .faq-q .faq-chevron { transform: rotate(180deg); }
.faq-a { padding: 0 var(--space-4) var(--space-3); font-size: .8rem; font-weight: 700; color: var(--ink-muted); line-height: 1.7; display: none; }
.faq-item.open .faq-a { display: block; }
@media (prefers-reduced-motion: reduce){ .faq-q .faq-chevron { transition: none; } }
</style>
</head>
<body>

{{-- ══════════════ HEADER ══════════════ --}}
<header class="appbar">
    <a href="{{ \App\Support\BackUrl::resolve(request(), 'dashboard.admin') }}" class="icon-btn" aria-label="Kembali">
        @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
    </a>
    <div class="appbar__head">
        <h1 class="appbar__title">Panduan Admin</h1>
        <p class="appbar__sub">Referensi operasional manajemen laundry pickup-delivery.</p>
    </div>
</header>

<main class="wrap">
    <div class="tab-row reveal d1">
        <button class="tab-btn active" onclick="switchTab('manajemen')">Manajemen Pesanan</button>
        <button class="tab-btn" onclick="switchTab('kurir')">Pengelolaan Kurir</button>
        <button class="tab-btn" onclick="switchTab('keuangan')">Keuangan</button>
        <button class="tab-btn" onclick="switchTab('faq')">FAQ</button>
    </div>

    {{-- TAB: MANAJEMEN PESANAN --}}
    <div class="tab-panel active reveal d2" id="tab-manajemen">
        <div class="section-title">
            @include('layouts.component._icon', ['name' => 'orders', 'size' => 20])
            Alur Manajemen Pesanan
        </div>
        <div class="flow">
            <div class="flow-item">
                <div class="flow-dot"></div>
                <div class="flow-text">
                    <div class="flow-label">Pesanan Masuk (Menunggu)</div>
                    <div class="flow-desc">Pelanggan membuat pesanan baru. Muncul di daftar "Belum Dijemput". Admin harus segera menugaskan kurir.</div>
                </div>
            </div>
            <div class="flow-item">
                <div class="flow-dot"></div>
                <div class="flow-text">
                    <div class="flow-label">Tugaskan Kurir Jemput</div>
                    <div class="flow-desc">Pilih kurir yang tersedia dan tugaskan untuk penjemputan. Status otomatis berubah ke "Dijemput".</div>
                </div>
            </div>
            <div class="flow-item">
                <div class="flow-dot"></div>
                <div class="flow-text">
                    <div class="flow-label">Proses di Outlet</div>
                    <div class="flow-desc">Setelah cucian tiba, update status ke "Dicuci" lalu "Disetrika". Kurir menginput berat aktual saat serah terima.</div>
                </div>
            </div>
            <div class="flow-item">
                <div class="flow-dot"></div>
                <div class="flow-text">
                    <div class="flow-label">Siap Diantar</div>
                    <div class="flow-desc">Update status ke "Siap". Kemudian tugaskan kurir untuk pengantaran. Status berubah ke "Dikirim".</div>
                </div>
            </div>
            <div class="flow-item">
                <div class="flow-dot flow-dot--done"></div>
                <div class="flow-text">
                    <div class="flow-label">Selesai</div>
                    <div class="flow-desc">Kurir mengkonfirmasi penyerahan dan pembayaran. Pesanan selesai. Pemasukan otomatis tercatat.</div>
                </div>
            </div>
        </div>

        <div class="section-title">
            @include('layouts.component._icon', ['name' => 'add-user', 'size' => 20])
            Fitur Pesanan Walk-in
        </div>
        <div class="card">
            <h4>Pelanggan Datang Langsung</h4>
            <p>Untuk pelanggan yang datang langsung ke outlet tanpa memesan via aplikasi:</p>
            <ul>
                <li>Buka menu "Tambah Pelanggan" atau "Walk-in" di navigasi</li>
                <li>Input nama dan nomor HP pelanggan</li>
                <li>Pilih layanan dan estimasi berat</li>
                <li>Sistem akan membuat akun pelanggan otomatis</li>
                <li>Status langsung masuk ke "Dicuci" (tanpa proses jemput)</li>
                <li>Tidak ada biaya ongkir untuk walk-in</li>
            </ul>
        </div>

        <div class="section-title">
            @include('layouts.component._icon', ['name' => 'edit', 'size' => 20])
            Update Status Manual
        </div>
        <div class="card">
            <h4>Status yang Bisa Diubah Admin</h4>
            <ul>
                <li><strong>Dicuci</strong> – Cucian masuk proses pencucian</li>
                <li><strong>Disetrika</strong> – Cucian selesai cuci, masuk setrika</li>
                <li><strong>Siap</strong> – Cucian selesai, siap untuk diantar</li>
                <li><strong>Selesai</strong> – Finalisasi manual (jika kurir tidak update)</li>
                <li><strong>Dibatalkan</strong> – Pembatalan pesanan (sebelum proses cuci)</li>
            </ul>
        </div>
    </div>

    {{-- TAB: KURIR --}}
    <div class="tab-panel reveal d2" id="tab-kurir">
        <div class="section-title">
            @include('layouts.component._icon', ['name' => 'pickup', 'size' => 20])
            Penugasan Kurir
        </div>
        <div class="card">
            <h4>Cara Menugaskan Kurir</h4>
            <ul>
                <li>Buka halaman "Pesanan" dan pilih pesanan yang berstatus "Menunggu"</li>
                <li>Pada panel penugasan, pilih kurir dari dropdown</li>
                <li>Pilih jenis tugas: "Pickup" (jemput) atau "Delivery" (antar)</li>
                <li>Tekan "Tugaskan" - kurir otomatis mendapat notifikasi</li>
                <li>Hanya kurir aktif yang muncul di daftar pilihan</li>
            </ul>
        </div>
        <div class="card">
            <h4>Pertimbangan Penugasan</h4>
            <ul>
                <li>Prioritaskan kurir yang berada di zona terdekat dengan pelanggan</li>
                <li>Perhatikan jumlah tugas aktif kurir (hindari overload)</li>
                <li>Untuk pesanan Express, tugaskan kurir yang paling cepat tersedia</li>
                <li>Pantau performa melalui total antar bulanan di profil kurir</li>
            </ul>
        </div>
        <div class="card card-tip">
            <h4>Monitoring Kurir</h4>
            <ul>
                <li>Cek jumlah "Kurir Aktif" di dashboard</li>
                <li>Pantau tugas yang belum di-update statusnya lebih dari 2 jam</li>
                <li>Hubungi kurir jika ada keluhan pelanggan soal keterlambatan</li>
                <li>Nonaktifkan akun kurir yang bermasalah melalui panel admin</li>
            </ul>
        </div>
    </div>

    {{-- TAB: KEUANGAN --}}
    <div class="tab-panel reveal d2" id="tab-keuangan">
        <div class="section-title">
            @include('layouts.component._icon', ['name' => 'finance', 'size' => 20])
            Laporan Keuangan
        </div>
        <div class="card">
            <h4>Pemasukan Otomatis</h4>
            <p>Setiap pesanan yang berhasil dibuat otomatis tercatat sebagai pemasukan di modul keuangan, termasuk:</p>
            <ul>
                <li>Biaya layanan (per kg atau per item)</li>
                <li>Ongkos kirim (sesuai zona)</li>
                <li>Pesanan walk-in</li>
            </ul>
        </div>
        <div class="card">
            <h4>Pencatatan Manual</h4>
            <p>Admin bisa menambahkan entri keuangan manual untuk:</p>
            <ul>
                <li>Pengeluaran operasional (detergen, listrik, sewa)</li>
                <li>Gaji karyawan dan komisi kurir</li>
                <li>Biaya perawatan kendaraan</li>
                <li>Pengeluaran tak terduga</li>
            </ul>
        </div>
        <div class="card">
            <h4>Export Laporan</h4>
            <p>Fitur export tersedia untuk mengunduh data keuangan dalam format spreadsheet. Berguna untuk pelaporan bulanan dan analisis.</p>
        </div>
    </div>

    {{-- TAB: FAQ --}}
    <div class="tab-panel reveal d2" id="tab-faq">
        <div class="section-title">
            @include('layouts.component._icon', ['name' => 'help', 'size' => 20])
            FAQ Admin
        </div>
        <div class="faq-card">
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">Pesanan sudah lama tapi belum dijemput?<span class="faq-chevron">@include('layouts.component._icon', ['name' => 'expand', 'size' => 20])</span></div>
                <div class="faq-a">Cek apakah kurir sudah ditugaskan. Jika sudah, hubungi kurir untuk konfirmasi. Jika belum, segera tugaskan kurir yang tersedia. Prioritaskan pesanan berdasarkan urutan waktu masuk.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">Pelanggan komplain cucian rusak/hilang?<span class="faq-chevron">@include('layouts.component._icon', ['name' => 'expand', 'size' => 20])</span></div>
                <div class="faq-a">Verifikasi klaim dengan mencocokkan catatan pesanan. Minta foto bukti dari pelanggan. Koordinasikan dengan tim outlet untuk pengecekan. Ganti rugi sesuai kebijakan (maks 10x biaya cuci item bersangkutan).</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">Kurir tidak update status?<span class="faq-chevron">@include('layouts.component._icon', ['name' => 'expand', 'size' => 20])</span></div>
                <div class="faq-a">Hubungi kurir untuk konfirmasi progress tugas. Jika kurir sudah menyelesaikan tapi lupa update, admin bisa update status secara manual melalui panel pesanan.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">Bagaimana membatalkan pesanan?<span class="faq-chevron">@include('layouts.component._icon', ['name' => 'expand', 'size' => 20])</span></div>
                <div class="faq-a">Pesanan bisa dibatalkan melalui panel status di halaman pesanan. Pilih status "Dibatalkan". Pastikan cucian belum masuk proses. Pelanggan akan mendapat notifikasi otomatis.</div>
            </div>
            <div class="faq-item">
                <div class="faq-q" onclick="toggleFaq(this)">Berat aktual sangat berbeda dari estimasi?<span class="faq-chevron">@include('layouts.component._icon', ['name' => 'expand', 'size' => 20])</span></div>
                <div class="faq-a">Total biaya otomatis dihitung ulang berdasarkan berat aktual saat kurir input di tahap serah terima. Pelanggan mendapat notifikasi perubahan. Tidak perlu intervensi admin kecuali ada komplain.</div>
            </div>
        </div>
    </div>

</main>

<script>
function toggleFaq(el){
  var item = el.parentElement;
  var wasOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(function(i){i.classList.remove('open');});
  if(!wasOpen) item.classList.add('open');
}
function switchTab(tabName){
  document.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('active');});
  document.querySelectorAll('.tab-panel').forEach(function(c){c.classList.remove('active');});
  event.target.classList.add('active');
  var target = document.getElementById('tab-'+tabName);
  if(target) target.classList.add('active');
}
</script>

@include('layouts.component.admin._navbar_admin', ['active' => ''])

</body>
</html>
