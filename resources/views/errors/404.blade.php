@php
    $code = 404; $icon = 'search'; $title = 'Halaman tidak ditemukan'; $accent = '#85092d';
    $message = 'Halaman yang kamu cari tidak ada. Mungkin URL-nya salah atau sudah dipindahkan.';
    $primary = auth()->check() ? ['url' => route('dashboard'), 'label' => 'Ke dasbor', 'icon' => 'home'] : ['url' => url('/'), 'label' => 'Kembali ke Beranda', 'icon' => 'home'];
    $secondary = ['url' => url('/'), 'label' => 'Halaman sebelumnya', 'icon' => 'undo-2', 'back' => true];
    $tip = 'Coba periksa kembali alamat URL yang kamu ketik.';
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary','tip'))
