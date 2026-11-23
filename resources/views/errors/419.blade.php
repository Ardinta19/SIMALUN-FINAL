@php
    $code = 419; $icon = 'clock'; $title = 'Sesi Kedaluwarsa'; $accent = '#b6435e';
    $message = 'Halaman ini terlalu lama dibuka tanpa aktivitas. Muat ulang dan coba lagi.';
    $primary = ['url' => 'javascript:location.reload()', 'label' => 'Muat Ulang', 'icon' => 'refresh-cw'];
    $secondary = ['url' => url('/'), 'label' => 'Ke Beranda', 'icon' => 'home'];
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary'))
