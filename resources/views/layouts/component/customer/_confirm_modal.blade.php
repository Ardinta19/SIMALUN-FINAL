{{--
    Alias lama untuk modal konfirmasi pelanggan.

    Berkas ini dulu salinan kedua dari modal konfirmasi dan menyimpang dari
    salinan bersama: default type-nya 'danger' (salah untuk dialog netral),
    `type-info` masih biru #005f92, dan dukungan `type-success` belum ada.
    Sekarang keduanya satu sumber: layouts.component._confirm_modal.
    Path lama masih dipakai roles/customer/profile/edit.blade.php supaya
    include di halaman itu tidak perlu diubah.
--}}
@include('layouts.component._confirm_modal')
