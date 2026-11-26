{{--
    Alias lama untuk paginasi pelanggan (`rental/index`, `customer/notifications`).

    Sebelumnya berkas ini punya sistem sendiri (`.bk-pagination`), sehingga
    daftar pelanggan dan daftar admin memakai dua rupa paginasi yang berbeda
    tinggi dan warnanya. Sekarang keduanya satu: `.bk-page-nav*` di
    `berkah-ui-kit.css` (44 px per butir, berlaku di tema terang, gelap, dan
    panel admin). Berkas ini dipertahankan supaya pemanggilan
    `links('vendor.pagination.berkah')` yang lama tetap bekerja.
--}}
@include('vendor.pagination.token-simple')
