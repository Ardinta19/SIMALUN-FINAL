<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Bantuan Kurir – Azka Laundry</title>
@include('layouts.component.customer._head_meta')
@include('layouts.component._tokens')
<style>
/* ═══════════════════════════════════════════════════════════
   AZKA LAUNDRY · KURIR · PUSAT BANTUAN
   Design System — token-only styling, Lucide icons, pure-CSS motion.
   All colors/radii/shadows/spacing reference canonical tokens
   emitted by layouts.component._tokens (mirrors tailwind.config.js).
═══════════════════════════════════════════════════════════ */
:root {
    /* Page-local aliases — only reference canonical tokens (no literals) */
    --ink:       var(--brand-900);
    --ink-mid:   color-mix(in srgb, var(--brand-900) 65%, var(--surface-400));
    --muted:     var(--surface-400);
    --line:      var(--surface-200);
    --font:      'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
    --nav-h:     64px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
    font-family: var(--font);
    font-size: 16px;
    background: var(--surface-50);
    color: var(--ink);
    min-height: 100vh;
    padding-bottom: calc(var(--nav-h) + var(--space-6) + env(safe-area-inset-bottom, 0px));
    overflow-x: hidden;
}

/* ── Entrance animation (pure CSS — transform/opacity only) ── */
@keyframes riseIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.reveal { opacity: 0; animation: riseIn .5s cubic-bezier(.22,.61,.36,1) forwards; }
.d1{animation-delay:.04s}.d2{animation-delay:.10s}.d3{animation-delay:.16s}
@media (prefers-reduced-motion: reduce){ .reveal{opacity:1;animation:none} }

/* ═══════════════════════════════════════════════ HERO */
.top {
    background: linear-gradient(145deg, var(--brand-900) 0%, var(--brand-500) 60%, var(--accent-500) 140%);
    color: #fff;
    position: relative;
    overflow: hidden;
}
.top-in {
    max-width: 520px;
    margin: 0 auto;
    padding: max(env(safe-area-inset-top, 0px), var(--space-5)) var(--space-4) var(--space-5);
}
.top-row { display: flex; align-items: center; gap: var(--space-3); }
.back-btn {
    width: var(--space-10); height: var(--space-10);
    border-radius: var(--radius-pill);
    background: rgba(255,255,255,.15);
    border: 1.5px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    color: #fff; text-decoration: none; flex-shrink: 0;
}
.tt { font-weight: 800; font-size: 1.15rem; flex: 1; }
.sub { font-size: .76rem; font-weight: 700; opacity: .85; margin-top: var(--space-1); padding-left: var(--space-12); }
.top-wave { display: block; width: 100%; margin-bottom: -2px; }
.wrap { max-width: 520px; margin: 0 auto; padding: var(--space-3) var(--space-4); }

/* Tabs */
.tab-row { display: flex; gap: var(--space-2); overflow-x: auto; padding-bottom: var(--space-2); margin-bottom: var(--space-4); scrollbar-width: none; }
.tab-row::-webkit-scrollbar { display: none; }
.tab-btn {
    white-space: nowrap;
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-pill);
    border: 1.5px solid var(--line);
    background: var(--surface-raised);
    font-size: .76rem; font-weight: 800;
    color: var(--ink-mid); cursor: pointer;
    transition: all .2s; font-family: var(--font);
}
.tab-btn.active { background: var(--brand-500); color: #fff; border-color: var(--brand-500); }
@media (prefers-reduced-motion: reduce){ .tab-btn { transition: none; } }
.tab-panel { display: none; } .tab-panel.active { display: block; }

/* Cards */
.section-title { font-weight: 800; font-size: .92rem; color: var(--ink); margin-bottom: var(--space-3); margin-top: var(--space-4); }
.section-title:first-child { margin-top: 0; }
.card {
    background: var(--surface-raised);
    border: 1.5px solid var(--line);
    border-radius: var(--radius-card);
    padding: var(--space-4);
    margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.card h4 { font-weight: 800; font-size: .88rem; margin-bottom: var(--space-2); color: var(--ink); }
.card p, .card li { font-size: .8rem; font-weight: 700; color: var(--muted); line-height: 1.7; }
.card ul { padding-left: var(--space-4); margin-top: var(--space-1); }
.card ul li { margin-bottom: var(--space-1); }
.card-warning { background: color-mix(in srgb, var(--warning-500) 10%, var(--surface-raised)); border-color: color-mix(in srgb, var(--warning-500) 32%, var(--surface-raised)); }
.card-warning h4 { color: color-mix(in srgb, var(--warning-500) 62%, #000); }
.card-warning p, .card-warning li { color: color-mix(in srgb, var(--warning-500) 72%, #000); }
.card-success { background: color-mix(in srgb, var(--success-500) 12%, var(--surface-raised)); border-color: color-mix(in srgb, var(--success-500) 30%, var(--surface-raised)); }
.card-success h4 { color: color-mix(in srgb, var(--success-500) 66%, #000); }
.card-success p, .card-success li { color: color-mix(in srgb, var(--success-500) 72%, #000); }

/* FAQ */
.faq-card {
    background: var(--surface-raised);
    border: 1.5px solid var(--line);
    border-radius: var(--radius-card);
    overflow: hidden;
    margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.faq-item { border-bottom: 1px solid var(--line); } .faq-item:last-child { border-bottom: none; }
.faq-q { display: flex; align-items: center; justify-content: space-between; padding: var(--space-3) var(--space-4); cursor: pointer; font-weight: 800; font-size: .84rem; gap: var(--space-2); user-select: none; }
.faq-q span { display: inline-flex; align-items: center; transition: transform .2s; flex-shrink: 0; color: var(--brand-500); }
.faq-item.open .faq-q span { transform: rotate(180deg); }
.faq-a { padding: 0 var(--space-4) var(--space-3); font-size: .78rem; font-weight: 700; color: var(--muted); line-height: 1.7; display: none; }
.faq-item.open .faq-a { display: block; }

/* Flow */
.flow { display: flex; flex-direction: column; gap: 0; margin: var(--space-2) 0; }
.flow-item { display: flex; align-items: flex-start; gap: var(--space-3); position: relative; padding-bottom: var(--space-4); }
.flow-item:last-child { padding-bottom: 0; }
.flow-dot { width: 10px; height: 10px; border-radius: var(--radius-pill); background: var(--brand-500); flex-shrink: 0; margin-top: 4px; position: relative; z-index: 1; }
.flow-item:not(:last-child) .flow-dot::after { content: ''; position: absolute; top: 10px; left: 4px; width: 2px; height: calc(100% + 8px); background: var(--line); }
.flow-text { flex: 1; }
.flow-label { font-weight: 900; font-size: .82rem; color: var(--ink); }
.flow-desc { font-size: .72rem; font-weight: 700; color: var(--muted); margin-top: 2px; }

/* Contact */
.contact-btn {
    display: flex; align-items: center; gap: var(--space-2);
    background: #25D366; color: #fff;
    padding: var(--space-3) var(--space-5);
    border-radius: var(--radius-btn);
    text-decoration: none; font-weight: 900; font-size: .82rem;
    margin-top: var(--space-3); justify-content: center;
    border: none; width: 100%; cursor: pointer;
    transition: transform .12s ease;
}
.contact-btn:active { transform: scale(.98); }
.contact-btn--report {
    background: color-mix(in srgb, var(--danger-500) 8%, var(--surface-raised));
    color: var(--danger-500);
    border: 1px solid color-mix(in srgb, var(--danger-500) 35%, var(--surface-raised));
    margin-top: var(--space-2);
}
@media (prefers-reduced-motion: reduce){ .contact-btn { transition: none; } }
</style>
</head>
<body>
<header class="top reveal d1">
  <div class="top-in">
    <div class="top-row">
      <a href="{{ route('driver.dashboard') }}" class="back-btn" aria-label="Kembali">
        @include('layouts.component._icon', ['name' => 'back', 'size' => 20, 'label' => 'Kembali'])
      </a>
      <div class="tt">Pusat Bantuan Kurir</div>
    </div>
    <div class="sub">Panduan operasional penjemputan dan pengantaran laundry.</div>
  </div>
  <svg class="top-wave" viewBox="0 0 1440 28" preserveAspectRatio="none" style="height:28px;"><path fill="#f8fafc" d="M0,14 C240,28 480,0 720,14 C960,28 1200,0 1440,14 L1440,28 L0,28Z"/></svg>
</header>

<main class="wrap">

  <!-- Tab Navigation -->
  <div class="tab-row reveal d2">
    <button class="tab-btn active" onclick="switchTab('panduan')">Panduan Tugas</button>
    <button class="tab-btn" onclick="switchTab('sop')">SOP Operasional</button>
    <button class="tab-btn" onclick="switchTab('faq')">FAQ</button>
    <button class="tab-btn" onclick="switchTab('kendala')">Penanganan Kendala</button>
    <button class="tab-btn" onclick="switchTab('kontak')">Kontak Admin</button>
  </div>

  <!-- TAB: PANDUAN TUGAS -->
  <div class="tab-panel active" id="tab-panduan">
    <div class="section-title">Alur Tugas Kurir</div>
    <div class="flow">
      <div class="flow-item">
        <div class="flow-dot"></div>
        <div class="flow-text">
          <div class="flow-label">1. Menerima Penugasan</div>
          <div class="flow-desc">Admin menugaskan pesanan ke akun Anda. Notifikasi masuk otomatis. Cek detail alamat dan waktu penjemputan.</div>
        </div>
      </div>
      <div class="flow-item">
        <div class="flow-dot"></div>
        <div class="flow-text">
          <div class="flow-label">2. Jemput Cucian</div>
          <div class="flow-desc">Datang ke alamat pelanggan sesuai jadwal. Konfirmasi jumlah cucian, timbang estimasi berat. Hubungi pelanggan jika perlu.</div>
        </div>
      </div>
      <div class="flow-item">
        <div class="flow-dot"></div>
        <div class="flow-text">
          <div class="flow-label">3. Serah Terima ke Outlet</div>
          <div class="flow-desc">Bawa cucian ke outlet. Update status pesanan menjadi "Dicuci" dan input berat aktual hasil penimbangan.</div>
        </div>
      </div>
      <div class="flow-item">
        <div class="flow-dot"></div>
        <div class="flow-text">
          <div class="flow-label">4. Antar Cucian Bersih</div>
          <div class="flow-desc">Saat cucian selesai dan admin menugaskan pengantaran, ambil dari outlet dan antar ke alamat pelanggan.</div>
        </div>
      </div>
      <div class="flow-item">
        <div class="flow-dot" style="background:var(--success-500);"></div>
        <div class="flow-text">
          <div class="flow-label">5. Konfirmasi Selesai</div>
          <div class="flow-desc">Setelah cucian diterima pelanggan, update status menjadi "Selesai". Upload foto bukti serah terima dan terima pembayaran COD.</div>
        </div>
      </div>
    </div>

    <div class="section-title">Cara Update Status</div>
    <div class="card">
      <h4>Melalui Halaman Detail Tugas</h4>
      <ul>
        <li>Buka tugas aktif dari halaman Beranda atau menu Tugas</li>
        <li>Tekan tombol aksi sesuai tahap (Jemput, Serah Terima, Antar, Selesai)</li>
        <li>Isi data yang diminta (berat aktual, foto bukti)</li>
        <li>Pelanggan akan otomatis mendapat notifikasi perubahan status</li>
      </ul>
    </div>
  </div>

  <!-- TAB: SOP OPERASIONAL -->
  <div class="tab-panel" id="tab-sop">
    <div class="section-title">Standar Operasional</div>
    <div class="card">
      <h4>Sebelum Berangkat</h4>
      <ul>
        <li>Pastikan kendaraan dalam kondisi baik dan bensin cukup</li>
        <li>Siapkan kantong laundry bersih untuk cucian pelanggan</li>
        <li>Cek detail alamat dan catatan khusus dari pelanggan</li>
        <li>Pastikan HP terisi penuh untuk komunikasi dan navigasi</li>
      </ul>
    </div>
    <div class="card">
      <h4>Saat Penjemputan</h4>
      <ul>
        <li>Hubungi pelanggan 5-10 menit sebelum tiba</li>
        <li>Gunakan seragam atau identitas Azka Laundry</li>
        <li>Konfirmasi nama pelanggan dan nomor pesanan</li>
        <li>Hitung dan catat estimasi jumlah pakaian</li>
        <li>Pastikan cucian sudah masuk kantong laundry rapi</li>
        <li>Ucapkan terima kasih dan informasikan estimasi selesai</li>
      </ul>
    </div>
    <div class="card">
      <h4>Saat Pengantaran</h4>
      <ul>
        <li>Pastikan cucian dalam keadaan rapi dan terlipat</li>
        <li>Hubungi pelanggan 5-10 menit sebelum sampai</li>
        <li>Serahkan cucian dan minta pelanggan cek kelengkapan</li>
        <li>Terima pembayaran COD (hitung di depan pelanggan)</li>
        <li>Foto bukti serah terima (cucian + pelanggan)</li>
        <li>Update status pesanan menjadi "Selesai" di aplikasi</li>
      </ul>
    </div>
    <div class="card">
      <h4>Ketentuan Waktu</h4>
      <ul>
        <li>Slot Pagi: tiba di lokasi pelanggan pukul 08.00 - 11.00</li>
        <li>Slot Siang: tiba di lokasi pelanggan pukul 12.00 - 15.00</li>
        <li>Slot Sore: tiba di lokasi pelanggan pukul 15.00 - 18.00</li>
        <li>Toleransi keterlambatan maksimal 15 menit dari slot waktu</li>
        <li>Jika terlambat lebih dari 15 menit, hubungi admin terlebih dahulu</li>
      </ul>
    </div>
    <div class="card card-warning">
      <h4>Yang Tidak Boleh Dilakukan</h4>
      <ul>
        <li>Membuka atau menggunakan cucian pelanggan</li>
        <li>Menolak tugas tanpa alasan jelas (hubungi admin jika ada kendala)</li>
        <li>Meminta pembayaran di luar tarif resmi</li>
        <li>Membagikan data pribadi pelanggan ke pihak lain</li>
        <li>Mengubah rute tanpa konfirmasi ke admin</li>
      </ul>
    </div>
  </div>

  <!-- TAB: FAQ -->
  <div class="tab-panel" id="tab-faq">
    <div class="section-title">Pertanyaan Umum Kurir</div>
    <div class="faq-card">
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Bagaimana jika pelanggan tidak ada di lokasi?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Hubungi pelanggan melalui tombol "Hubungi" di detail tugas. Tunggu maksimal 10 menit. Jika tetap tidak ada respon, laporkan ke admin melalui WhatsApp dan tunggu instruksi selanjutnya.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Alamat pelanggan tidak ditemukan?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Hubungi pelanggan untuk konfirmasi titik jemput yang tepat. Minta patokan atau landmark terdekat. Jika masih kesulitan, hubungi admin untuk mediasi.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Bagaimana jika cucian melebihi kapasitas motor?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Laporkan ke admin sebelum meninggalkan lokasi pelanggan. Admin akan mengatur penjemputan kedua atau menugaskan kurir tambahan.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Pelanggan minta tambah cucian di luar pesanan?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Minta pelanggan membuat pesanan baru melalui aplikasi. Kurir tidak bisa menambah item di luar pesanan yang sudah terdaftar untuk menjaga akurasi data.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Kendaraan mogok saat tugas?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Segera hubungi admin via WhatsApp. Amankan cucian pelanggan. Admin akan mengalihkan tugas ke kurir lain atau mengatur solusi darurat.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Pelanggan menolak menerima cucian?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Tanyakan alasan penolakan dengan sopan. Jika ada keluhan (cucian tidak bersih, item hilang, dll), catat dan laporkan langsung ke admin. Jangan meninggalkan lokasi sebelum ada instruksi admin.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Pembayaran COD kurang?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Tunjukkan detail tagihan dari aplikasi ke pelanggan. Jika pelanggan tetap tidak bisa membayar penuh, laporkan ke admin dan jangan serahkan cucian sampai pembayaran lunas.</div>
      </div>
      <div class="faq-item">
        <div class="faq-q" onclick="toggleFaq(this)">Bagaimana cara melihat riwayat tugas?<span>@include('layouts.component._icon', ['name' => 'expand', 'size' => 16])</span></div>
        <div class="faq-a">Buka menu "Tugas" di navigasi bawah. Semua tugas yang pernah ditugaskan (aktif maupun selesai) akan ditampilkan beserta detailnya.</div>
      </div>
    </div>
  </div>

  <!-- TAB: KENDALA -->
  <div class="tab-panel" id="tab-kendala">
    <div class="section-title">Penanganan Situasi Darurat</div>
    <div class="card card-warning">
      <h4>Kecelakaan atau Kehilangan Cucian</h4>
      <ul>
        <li>Utamakan keselamatan diri sendiri</li>
        <li>Hubungi admin segera via telepon atau WhatsApp</li>
        <li>Dokumentasikan kejadian dengan foto</li>
        <li>Jangan tinggalkan lokasi sebelum cucian diamankan</li>
        <li>Admin akan mengkoordinasikan penyelesaian ke pelanggan</li>
      </ul>
    </div>
    <div class="card">
      <h4>Hujan Deras / Cuaca Buruk</h4>
      <ul>
        <li>Pastikan cucian terlindung dari air (gunakan plastik penutup)</li>
        <li>Jika kondisi tidak memungkinkan untuk berkendara, berteduh dan hubungi admin</li>
        <li>Informasikan keterlambatan ke pelanggan via chat</li>
        <li>Admin akan menyesuaikan jadwal jika diperlukan</li>
      </ul>
    </div>
    <div class="card">
      <h4>Pelanggan Bersikap Tidak Sopan</h4>
      <ul>
        <li>Tetap tenang dan profesional</li>
        <li>Jangan membalas dengan emosi</li>
        <li>Selesaikan tugas sesuai SOP</li>
        <li>Laporkan kejadian ke admin setelahnya</li>
        <li>Admin akan menindaklanjuti ke pelanggan bersangkutan</li>
      </ul>
    </div>
    <div class="card card-success">
      <h4>Tips Efisiensi Rute</h4>
      <ul>
        <li>Cek semua tugas di pagi hari dan rencanakan rute terpendek</li>
        <li>Selesaikan tugas berdasarkan urutan zona terdekat</li>
        <li>Gabungkan penjemputan dan pengantaran di area yang sama</li>
        <li>Gunakan navigasi untuk menghindari kemacetan</li>
      </ul>
    </div>
  </div>

  <!-- TAB: KONTAK -->
  <div class="tab-panel" id="tab-kontak">
    <div class="section-title">Kontak Admin Operasional</div>
    <div class="card">
      <h4>Kapan Harus Menghubungi Admin?</h4>
      <ul>
        <li>Pelanggan tidak bisa dihubungi lebih dari 10 menit</li>
        <li>Alamat tidak ditemukan setelah mencoba konfirmasi</li>
        <li>Kendala kendaraan saat membawa cucian</li>
        <li>Pelanggan menolak menerima cucian</li>
        <li>Situasi darurat (kecelakaan, kehilangan)</li>
        <li>Ingin mengajukan izin atau tukar jadwal</li>
      </ul>
    </div>
    <a href="https://wa.me/6281234567890?text=Halo%20Admin%20Azka%20Laundry%2C%20saya%20kurir%20{{ urlencode(Auth::user()->name ?? '') }}%20perlu%20bantuan" target="_blank" class="contact-btn">
      @include('layouts.component._icon', ['name' => 'whatsapp', 'size' => 20])
      Hubungi Admin via WhatsApp
    </a>
    <a href="{{ route('driver.report') }}" class="contact-btn contact-btn--report">
      @include('layouts.component._icon', ['name' => 'alert', 'size' => 20])
      Lapor Kendala / Bug
    </a>
    <div style="margin-top:var(--space-3);"></div>
    <div class="card">
      <h4>Jam Kerja Admin</h4>
      <ul>
        <li>Senin - Sabtu: 07.00 - 21.00 WIB</li>
        <li>Minggu: 08.00 - 18.00 WIB</li>
        <li>Darurat di luar jam kerja: tetap hubungi via WA</li>
      </ul>
    </div>
  </div>

</main>

{{-- ══════════════ DRIVER NAV ══════════════ --}}
@include('layouts.component.driver._navbar_driver', ['active' => ''])

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
</body>
</html>
