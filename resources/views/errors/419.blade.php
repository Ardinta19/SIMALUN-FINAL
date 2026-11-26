@php
    $code = 419; $icon = 'clock'; $title = 'Sesi kedaluwarsa'; $accent = '#b6435e';
    $message = 'Halaman ini terlalu lama dibuka tanpa aktivitas. Muat ulang dan coba lagi.';
    $primary = ['url' => url('/'), 'label' => 'Muat ulang', 'icon' => 'refresh-cw', 'reload' => true];
    $secondary = ['url' => url('/'), 'label' => 'Ke beranda', 'icon' => 'home'];
@endphp
@include('errors.layout', compact('code','icon','title','message','accent','primary','secondary'))
