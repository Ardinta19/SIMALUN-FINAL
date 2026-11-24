@php
    $code = 404; $icon = 'search'; $title = 'Halaman Tidak Ditemukan'; $accent = '#85092d';
    $message = 'Halaman yang kamu cari tidak ada. Mungkin URL-nya salah atau sudah dipindahkan.';
    $primary = auth()->check() ? ['url' => route('dashboard'), 'label' => 'Ke Dasbor', 'icon' => 'home'] : ['url' => url('/'), 'label' => 'Kembali ke Beranda', 'icon' => 'home'];
    $secondary = ['url' => url('/'), 'label' => 'Halaman Sebelumnya', 'icon' => 'undo-2', 'back' => true];
    $tip = 'Coba periksa kembali alamat URL yang kamu ketik.';
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary','tip'))
