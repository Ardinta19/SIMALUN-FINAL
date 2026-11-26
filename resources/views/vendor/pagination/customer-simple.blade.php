{{--
    Alias paginasi pelanggan yang lama.

    Berkas ini dulu membawa `<style>` sebaris sendiri (`.pg*`) dengan butir
    setinggi ~32 px — sistem paginasi ketiga setelah `.bk-pagination` dan
    tampilan bawaan Laravel. Sekarang semuanya satu: `.bk-page-nav*`
    (`berkah-ui-kit.css`), 44 px, berlaku di tema terang, gelap, dan panel
    admin. Alias dipertahankan agar pemanggilan lama tetap bekerja.
--}}
@include('vendor.pagination.token-simple')
