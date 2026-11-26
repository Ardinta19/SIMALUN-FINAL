{{-- Tautan lompat ke isi: elemen pertama di <body> supaya pengguna papan ketik
     dan pembaca layar tidak harus melewati header di setiap halaman. Dipakai
     halaman mandiri (auth, dasbor admin, lembar cetak, halaman galat); halaman
     ber-layout sudah punya tautan serupa (`.bk-skip` / `.adm-skip`).

     Gaya ditulis di sini (bukan di berkas CSS layout) karena halaman mandiri
     tidak memuat stylesheet layout. Tautan disembunyikan di luar fokus, tidak
     memengaruhi tata letak karena memakai `position: fixed`. --}}
@php $skipTarget = $target ?? 'main-content'; @endphp
<a class="bk-skip-link" href="#{{ $skipTarget }}">Langsung ke isi</a>
<style>
    .bk-skip-link {
        position: fixed; left: 12px; top: -72px; z-index: 9999;
        padding: 12px 18px; border-radius: 12px;
        background: #85092d; color: #fff;
        font: 700 14px/1.3 'Plus Jakarta Sans', system-ui, sans-serif;
        text-decoration: none; box-shadow: 0 10px 24px rgba(84, 5, 31, .28);
        transition: top .16s ease;
    }
    .bk-skip-link:focus,
    .bk-skip-link:focus-visible {
        top: max(12px, env(safe-area-inset-top, 0px));
        outline: 3px solid #bc5b75; outline-offset: 2px;
    }
    @media (prefers-reduced-motion: reduce) { .bk-skip-link { transition: none; } }
    @media print { .bk-skip-link { display: none; } }
</style>
